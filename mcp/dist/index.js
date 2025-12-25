import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { CallToolRequestSchema, ListToolsRequestSchema } from "@modelcontextprotocol/sdk/types.js";
import dotenv from "dotenv";
import fs from "node:fs/promises";
import path from "node:path";
import { randomUUID } from "node:crypto";
import YAML from "yaml";
dotenv.config();
const baseUrl = process.env.MCP_API_BASE_URL ?? "http://localhost:8081";
const server = new Server({
    name: "tv-mcp-server",
    version: "1.0.0",
}, {
    capabilities: {
        tools: {},
    },
});
const wizardStore = new Map();
const MAKERS = ["SONY", "Panasonic", "TOSHIBA", "SHARP", "LG", "SAMSUNG"];
const RESOLUTIONS = ["HD", "FullHD", "4K", "8K"];
const PANELS = ["LCD", "OLED", "MiniLED"];
const INCHES = [32, 43, 50, 55, 65, 75];
const HDMI_PORTS = [1, 2, 3, 4, 5, 6];
const OPTION_CATEGORIES = ["SOUND", "WALL", "STORAGE", "EXTENDED_WARRANTY"];
async function readOpenApi(openapiPath) {
    const resolved = path.resolve(process.cwd(), openapiPath);
    const raw = await fs.readFile(resolved, "utf-8");
    if (openapiPath.endsWith(".json")) {
        return JSON.parse(raw);
    }
    return YAML.parse(raw);
}
function jsonResponse(payload) {
    return {
        content: [
            {
                type: "text",
                text: JSON.stringify(payload, null, 2),
            },
        ],
    };
}
async function callApi(method, apiPath, query, body) {
    const url = new URL(apiPath, baseUrl);
    if (query) {
        for (const [key, value] of Object.entries(query)) {
            if (value === undefined || value === null) {
                continue;
            }
            url.searchParams.set(key, String(value));
        }
    }
    const response = await fetch(url.toString(), {
        method,
        headers: {
            "Content-Type": "application/json",
        },
        body: body ? JSON.stringify(body) : undefined,
    });
    const contentType = response.headers.get("content-type") ?? "";
    let data = null;
    if (contentType.includes("application/json")) {
        data = await response.json();
    }
    else if (response.status !== 204) {
        data = await response.text();
    }
    return {
        status: response.status,
        data,
    };
}
function getWizard(wizardId) {
    const state = wizardStore.get(wizardId);
    if (!state) {
        throw new Error(`Wizard not found: ${wizardId}`);
    }
    return state;
}
function buildWizardSummary(state) {
    return {
        ...state.data,
        selected_options: state.selectedOptions.map((option) => ({
            tv_option_id: option.tv_option_id,
            quantity: option.quantity,
            label: option.label,
            price_yen: option.price_yen,
        })),
    };
}
function defaultTvName(state) {
    const maker = state.data.maker ?? "TV";
    const inch = state.data.inch ? `${state.data.inch}"` : "";
    return `${maker} ${inch}`.trim();
}
server.setRequestHandler(ListToolsRequestSchema, async () => {
    const tools = [
        {
            name: "api.list_tools_from_openapi",
            description: "Read an OpenAPI file and list available endpoints.",
            inputSchema: {
                type: "object",
                properties: {
                    openapiPath: {
                        type: "string",
                        description: "Relative path to openapi.yaml or openapi.json",
                    },
                },
                required: ["openapiPath"],
            },
        },
        {
            name: "api.call",
            description: "Call the Laravel API.",
            inputSchema: {
                type: "object",
                properties: {
                    method: { type: "string", description: "HTTP method" },
                    path: { type: "string", description: "Path starting with /api" },
                    query: { type: "object" },
                    body: { type: ["object", "array", "string", "number", "boolean", "null"] },
                },
                required: ["method", "path"],
            },
        },
        {
            name: "tvWizard.start",
            description: "Start a TV registration wizard.",
            inputSchema: { type: "object", properties: {} },
        },
        {
            name: "tvWizard.next",
            description: "Get the next wizard question.",
            inputSchema: {
                type: "object",
                properties: {
                    wizard_id: { type: "string" },
                },
                required: ["wizard_id"],
            },
        },
        {
            name: "tvWizard.submit",
            description: "Submit an answer to the wizard.",
            inputSchema: {
                type: "object",
                properties: {
                    wizard_id: { type: "string" },
                    field: { type: "string" },
                    value: {},
                },
                required: ["wizard_id", "field", "value"],
            },
        },
        {
            name: "tvWizard.confirm",
            description: "Preview payload and estimated price.",
            inputSchema: {
                type: "object",
                properties: {
                    wizard_id: { type: "string" },
                },
                required: ["wizard_id"],
            },
        },
        {
            name: "tvWizard.create",
            description: "Create the TV via API.",
            inputSchema: {
                type: "object",
                properties: {
                    wizard_id: { type: "string" },
                },
                required: ["wizard_id"],
            },
        },
        {
            name: "tvWizard.reset",
            description: "Reset and remove wizard state.",
            inputSchema: {
                type: "object",
                properties: {
                    wizard_id: { type: "string" },
                },
                required: ["wizard_id"],
            },
        },
    ];
    return { tools };
});
server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;
    try {
        switch (name) {
            case "api.list_tools_from_openapi": {
                const openapiPath = String(args?.openapiPath ?? "");
                const doc = await readOpenApi(openapiPath);
                const tools = [];
                for (const [apiPath, methods] of Object.entries(doc.paths ?? {})) {
                    for (const method of ["get", "post", "put", "patch", "delete"]) {
                        const operation = methods?.[method];
                        if (!operation) {
                            continue;
                        }
                        const parameters = [...(methods?.parameters ?? []), ...(operation.parameters ?? [])];
                        const requestBody = operation.requestBody ?? null;
                        tools.push({
                            name: `${method.toUpperCase()} ${apiPath}`,
                            description: operation.summary ?? operation.description ?? "",
                            params: {
                                parameters,
                                requestBody,
                            },
                        });
                    }
                }
                return jsonResponse({ tools });
            }
            case "api.call": {
                const method = String(args?.method ?? "GET").toUpperCase();
                const apiPath = String(args?.path ?? "");
                const query = args?.query ?? undefined;
                const body = args?.body ?? undefined;
                const result = await callApi(method, apiPath, query, body);
                return jsonResponse(result);
            }
            case "tvWizard.start": {
                const id = randomUUID();
                wizardStore.set(id, {
                    id,
                    phase: "maker",
                    data: {},
                    selectedOptions: [],
                });
                return jsonResponse({ wizard_id: id });
            }
            case "tvWizard.next": {
                const wizardId = String(args?.wizard_id ?? "");
                const state = getWizard(wizardId);
                switch (state.phase) {
                    case "maker":
                        return jsonResponse({
                            question: "Select maker",
                            field: "maker",
                            choices: MAKERS,
                            input_type: "select",
                        });
                    case "inch":
                        return jsonResponse({
                            question: "Select inch size",
                            field: "inch",
                            choices: INCHES,
                            input_type: "select",
                        });
                    case "resolution":
                        return jsonResponse({
                            question: "Select resolution",
                            field: "resolution",
                            choices: RESOLUTIONS,
                            input_type: "select",
                        });
                    case "panel":
                        return jsonResponse({
                            question: "Select panel type (8K is best with MiniLED)",
                            field: "panel",
                            choices: PANELS,
                            input_type: "select",
                        });
                    case "hdmi_ports":
                        return jsonResponse({
                            question: "Select HDMI ports",
                            field: "hdmi_ports",
                            choices: HDMI_PORTS,
                            input_type: "select",
                        });
                    case "has_hdr":
                        return jsonResponse({
                            question: "HDR support?",
                            field: "has_hdr",
                            choices: ["yes", "no"],
                            input_type: "boolean",
                        });
                    case "has_wifi":
                        return jsonResponse({
                            question: "Wi-Fi support?",
                            field: "has_wifi",
                            choices: ["yes", "no"],
                            input_type: "boolean",
                        });
                    case "option_category":
                        return jsonResponse({
                            question: "Select option category (or finish)",
                            field: "option_category",
                            choices: [...OPTION_CATEGORIES, "FINISH"],
                            input_type: "select",
                        });
                    case "option_choice":
                        return jsonResponse({
                            question: `Select option in ${state.optionCategory}`,
                            field: "option_id",
                            choices: (state.optionChoices ?? []).map((option) => ({
                                id: option.id,
                                label: `${option.label} (${option.price_yen} yen)`,
                            })),
                            input_type: "select",
                        });
                    case "option_quantity":
                        return jsonResponse({
                            question: "Select quantity",
                            field: "option_quantity",
                            choices: [1, 2, 3, 4, 5],
                            input_type: "select",
                        });
                    case "option_continue":
                        return jsonResponse({
                            question: "Add more options?",
                            field: "option_action",
                            choices: ["SAME_CATEGORY", "ANOTHER_CATEGORY", "FINISH"],
                            input_type: "select",
                        });
                    case "confirm":
                        return jsonResponse({
                            question: "Ready to confirm",
                            field: "confirm",
                            choices: ["confirm"],
                            input_type: "confirm",
                        });
                    default:
                        return jsonResponse({ message: "Wizard complete" });
                }
            }
            case "tvWizard.submit": {
                const wizardId = String(args?.wizard_id ?? "");
                const field = String(args?.field ?? "");
                const value = args?.value;
                const state = getWizard(wizardId);
                switch (field) {
                    case "maker":
                        state.data.maker = String(value);
                        state.phase = "inch";
                        break;
                    case "inch":
                        state.data.inch = Number(value);
                        state.phase = "resolution";
                        break;
                    case "resolution":
                        state.data.resolution = String(value);
                        state.phase = "panel";
                        break;
                    case "panel":
                        state.data.panel = String(value);
                        state.phase = "hdmi_ports";
                        break;
                    case "hdmi_ports":
                        state.data.hdmi_ports = Number(value);
                        state.phase = "has_hdr";
                        break;
                    case "has_hdr":
                        state.data.has_hdr = String(value).toLowerCase() === "yes" || value === true;
                        state.phase = "has_wifi";
                        break;
                    case "has_wifi":
                        state.data.has_wifi = String(value).toLowerCase() === "yes" || value === true;
                        state.phase = "option_category";
                        break;
                    case "option_category": {
                        const category = String(value);
                        if (category === "FINISH") {
                            state.phase = "confirm";
                            break;
                        }
                        state.optionCategory = category;
                        const result = await callApi("GET", "/api/tv-options", { category });
                        const options = Array.isArray(result.data) ? result.data : [];
                        state.optionChoices = options.map((item) => ({
                            id: Number(item.id),
                            category: String(item.category),
                            code: String(item.code),
                            label: String(item.label),
                            price_yen: Number(item.price_yen),
                        }));
                        state.phase = "option_choice";
                        break;
                    }
                    case "option_id": {
                        const optionId = Number(value);
                        const choice = state.optionChoices?.find((option) => option.id === optionId);
                        if (!choice) {
                            throw new Error("Option not found in current category");
                        }
                        state.optionChoices = [choice];
                        state.phase = "option_quantity";
                        break;
                    }
                    case "option_quantity": {
                        const choice = state.optionChoices?.[0];
                        if (!choice) {
                            throw new Error("No option selected");
                        }
                        const quantity = Number(value);
                        state.selectedOptions.push({
                            tv_option_id: choice.id,
                            quantity,
                            price_yen: choice.price_yen,
                            label: choice.label,
                        });
                        state.optionChoices = undefined;
                        state.phase = "option_continue";
                        break;
                    }
                    case "option_action": {
                        const action = String(value);
                        if (action === "SAME_CATEGORY") {
                            if (state.optionCategory) {
                                const result = await callApi("GET", "/api/tv-options", {
                                    category: state.optionCategory,
                                });
                                const options = Array.isArray(result.data) ? result.data : [];
                                state.optionChoices = options.map((item) => ({
                                    id: Number(item.id),
                                    category: String(item.category),
                                    code: String(item.code),
                                    label: String(item.label),
                                    price_yen: Number(item.price_yen),
                                }));
                            }
                            state.phase = "option_choice";
                        }
                        else if (action === "ANOTHER_CATEGORY") {
                            state.phase = "option_category";
                        }
                        else {
                            state.phase = "confirm";
                        }
                        break;
                    }
                    default:
                        throw new Error(`Unknown field: ${field}`);
                }
                wizardStore.set(wizardId, state);
                return jsonResponse({ ok: true, state_summary: buildWizardSummary(state) });
            }
            case "tvWizard.confirm": {
                const wizardId = String(args?.wizard_id ?? "");
                const state = getWizard(wizardId);
                const total = state.selectedOptions.reduce((sum, option) => sum + option.price_yen * option.quantity, 0);
                return jsonResponse({
                    payload_preview: {
                        ...state.data,
                        name: state.data.name ?? defaultTvName(state),
                        selected_options: state.selectedOptions.map((option) => ({
                            tv_option_id: option.tv_option_id,
                            quantity: option.quantity,
                        })),
                    },
                    estimated_total_price_yen: total,
                });
            }
            case "tvWizard.create": {
                const wizardId = String(args?.wizard_id ?? "");
                const state = getWizard(wizardId);
                const payload = {
                    name: state.data.name ?? defaultTvName(state),
                    maker: state.data.maker,
                    inch: state.data.inch,
                    resolution: state.data.resolution,
                    panel: state.data.panel,
                    hdmi_ports: state.data.hdmi_ports,
                    has_hdr: state.data.has_hdr,
                    has_wifi: state.data.has_wifi,
                    selected_options: state.selectedOptions.map((option) => ({
                        tv_option_id: option.tv_option_id,
                        quantity: option.quantity,
                    })),
                };
                const result = await callApi("POST", "/api/tvs", undefined, payload);
                return jsonResponse(result);
            }
            case "tvWizard.reset": {
                const wizardId = String(args?.wizard_id ?? "");
                wizardStore.delete(wizardId);
                return jsonResponse({ ok: true });
            }
            default:
                throw new Error(`Unknown tool: ${name}`);
        }
    }
    catch (error) {
        const message = error instanceof Error ? error.message : String(error);
        return jsonResponse({ error: message });
    }
});
const transport = new StdioServerTransport();
await server.connect(transport);
