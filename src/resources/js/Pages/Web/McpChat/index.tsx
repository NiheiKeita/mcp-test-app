import React, { useMemo, useState } from 'react'
import WebLayout from '@/Layouts/WebLayout'

type ChatMessage = {
    id: string
    role: 'system' | 'user' | 'assistant'
    title: string
    content: string
}

type ChatResponse = {
    message?: {
        role?: string
        content?: string | null
    }
    tool_calls?: Array<{
        name: string
        arguments: Record<string, unknown>
        result: unknown
    }>
}

export const McpChat = React.memo(function McpChat() {
    const [messages, setMessages] = useState<ChatMessage[]>([
        {
            id: 'system-1',
            role: 'system',
            title: 'MCP Chat',
            content: 'Ask in plain Japanese. The assistant will call MCP tools as needed.',
        },
    ])
    const [inputText, setInputText] = useState('')
    const [isLoading, setIsLoading] = useState(false)
    const [errorText, setErrorText] = useState<string | null>(null)

    const apiBaseUrl = useMemo(() => {
        return import.meta.env.VITE_MCP_CHAT_URL ?? ''
    }, [])

    const pushMessage = (message: ChatMessage) => {
        setMessages((prev) => [...prev, message])
    }

    const handleSend = async () => {
        setErrorText(null)
        if (!inputText.trim()) {
            setErrorText('メッセージを入力してください。')
            return
        }

        const userMessage: ChatMessage = {
            id: crypto.randomUUID(),
            role: 'user',
            title: 'User',
            content: inputText.trim(),
        }

        pushMessage(userMessage)
        setInputText('')

        setIsLoading(true)
        try {
            const response = await fetch(`${apiBaseUrl}/api/mcp-chat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    messages: messages
                        .filter((message) => message.role !== 'system')
                        .concat(userMessage)
                        .map((message) => ({
                            role: message.role,
                            content: message.content,
                        })),
                }),
            })

            const data = (await response.json()) as ChatResponse

            if (data.tool_calls && data.tool_calls.length > 0) {
                data.tool_calls.forEach((toolCall) => {
                    pushMessage({
                        id: crypto.randomUUID(),
                        role: 'assistant',
                        title: `Tool: ${toolCall.name}`,
                        content: JSON.stringify(toolCall.result, null, 2),
                    })
                })
            }

            pushMessage({
                id: crypto.randomUUID(),
                role: 'assistant',
                title: 'Assistant',
                content: data.message?.content ?? 'No response content.',
            })
        } catch (error) {
            pushMessage({
                id: crypto.randomUUID(),
                role: 'assistant',
                title: 'Error',
                content: 'Failed to reach the chat endpoint. Check MCP server and API settings.',
            })
        } finally {
            setIsLoading(false)
        }
    }

    return (
        <WebLayout>
            <div
                className="relative mt-6 w-full overflow-hidden rounded-3xl border border-slate-200 bg-white/70 p-6 shadow-[0_30px_90px_-50px_rgba(15,23,42,0.6)] backdrop-blur"
                style={{
                    backgroundImage:
                        'radial-gradient(circle at top, rgba(14,165,233,0.15), transparent 55%), radial-gradient(circle at 20% 20%, rgba(250,204,21,0.2), transparent 50%)',
                }}
            >
                <div className="flex flex-col gap-6 lg:flex-row">
                    <div className="flex-1">
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <h1
                                    className="text-2xl font-semibold text-slate-900"
                                    style={{ fontFamily: '"Avenir Next", "Hiragino Kaku Gothic ProN", "Yu Gothic", sans-serif' }}
                                >
                                    MCP Chat Console
                                </h1>
                                <p className="mt-1 text-sm text-slate-600">
                                    Backend: <span className="font-semibold text-slate-900">{apiBaseUrl || 'same origin'}</span>
                                </p>
                            </div>
                            <span className="rounded-full bg-slate-900 px-3 py-1 text-xs uppercase tracking-[0.2em] text-white">
                                Local
                            </span>
                        </div>

                        <div className="space-y-4">
                            {messages.map((message) => (
                                <div
                                    key={message.id}
                                    className={
                                        message.role === 'user'
                                            ? 'rounded-2xl bg-slate-900 px-4 py-3 text-white'
                                            : message.role === 'assistant'
                                                ? 'rounded-2xl bg-white px-4 py-3 text-slate-900 shadow-sm'
                                                : 'rounded-2xl bg-slate-100 px-4 py-3 text-slate-700'
                                    }
                                >
                                    <p className="text-xs uppercase tracking-[0.2em] opacity-70">
                                        {message.title}
                                    </p>
                                    <pre className="mt-2 whitespace-pre-wrap text-sm leading-relaxed">
                                        {message.content}
                                    </pre>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="w-full max-w-xl space-y-4 lg:w-96">
                        <div className="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                            <h2 className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Message</h2>
                            <textarea
                                className="mt-2 h-40 w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-900"
                                placeholder="例: テレビ登録を進めて。おすすめのメーカーで55インチ、4K、Wi-Fiありで。"
                                value={inputText}
                                onChange={(event) => setInputText(event.target.value)}
                            />
                            {errorText ? <p className="mt-2 text-xs text-red-500">{errorText}</p> : null}
                            <button
                                type="button"
                                className="mt-4 w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                onClick={handleSend}
                                disabled={isLoading}
                            >
                                {isLoading ? 'Sending...' : 'Send'}
                            </button>
                        </div>

                        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-xs text-slate-500">
                            <p className="font-semibold text-slate-600">Prompt ideas</p>
                            <ul className="mt-2 list-disc pl-4">
                                <li>テレビ登録を進めて。55インチの4Kでおすすめを選んで</li>
                                <li>HDRありでSONYのモデルを登録して</li>
                                <li>サウンドバーと延長保証を付けたい</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </WebLayout>
    )
})

export default McpChat
