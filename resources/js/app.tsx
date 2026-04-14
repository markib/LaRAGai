import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';

interface QueryResponse {
  answer: string;
  documents: Array<{ id: number; source: string; content: string }>;
  session_id?: string | null;
}

type ChatMessage = {
  role: 'user' | 'assistant';
  content: string;
};

function App() {
  const [query, setQuery] = useState('');
  const [sessionId, setSessionId] = useState<string>('');
  const [chatHistory, setChatHistory] = useState<ChatMessage[]>([]);
  const [documents, setDocuments] = useState<QueryResponse['documents']>([]);
  const [response, setResponse] = useState<QueryResponse | null>(null);
  const [pendingAnswer, setPendingAnswer] = useState('');
  const [streamedAnswer, setStreamedAnswer] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const typingIntervalRef = useRef<number | null>(null);

  const clearTyping = () => {
    if (typingIntervalRef.current !== null) {
      window.clearInterval(typingIntervalRef.current);
      typingIntervalRef.current = null;
    }
  };

  const readJsonFromStream = async (body: ReadableStream<Uint8Array> | null) => {
    if (!body) {
      throw new Error('Readable stream is not available.');
    }

    const reader = body.getReader();
    const decoder = new TextDecoder();
    let raw = '';
    let done = false;

    while (!done) {
      const result = await reader.read();
      done = result.done ?? false;
      if (result.value) {
        raw += decoder.decode(result.value, { stream: !done });
      }
    }

    return JSON.parse(raw) as QueryResponse;
  };

  const submitQuery = async () => {
    const trimmedQuery = query.trim();
    if (!trimmedQuery) {
      return;
    }

    clearTyping();
    setLoading(true);
    setError('');
    setChatHistory((prev) => [...prev, { role: 'user', content: trimmedQuery }]);
    setResponse(null);
    setDocuments([]);
    setPendingAnswer('');
    setStreamedAnswer('');
    setQuery('');

    try {
      const payload = { query: trimmedQuery, session_id: sessionId || undefined };
      const res = await fetch('/api/rag/query', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      if (!res.ok) {
        throw new Error(`Request failed with status ${res.status}`);
      }

      const data: QueryResponse = res.body
        ? await readJsonFromStream(res.body)
        : await res.json();

      setResponse(data);
      setDocuments(data.documents);
      setPendingAnswer(data.answer);
      if (!sessionId && data.session_id) {
        setSessionId(data.session_id);
      }
    } catch (err) {
      setError((err as Error).message);
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!pendingAnswer) {
      return;
    }

    let index = 0;
    setStreamedAnswer('');

    typingIntervalRef.current = window.setInterval(() => {
      index += 1;
      setStreamedAnswer(pendingAnswer.slice(0, index));

      if (index >= pendingAnswer.length) {
        clearTyping();
        setChatHistory((prev) => [...prev, { role: 'assistant', content: pendingAnswer }]);
        setPendingAnswer('');
        setLoading(false);
      }
    }, 18);

    return () => {
      clearTyping();
    };
  }, [pendingAnswer]);

  return (
    <div className="app-shell">
      <header className="app-header">
        <h1>LaRAGai</h1>
        <p>Local Ollama-powered RAG assistant with Laravel + React.</p>
      </header>

      <div className="card">
        <div className="chat-window">
          {chatHistory.map((message, index) => (
            <div key={index} className={`message ${message.role}`}>
              {message.content}
            </div>
          ))}

          {pendingAnswer ? (
            <div className="message assistant typing">
              {streamedAnswer}
              <span className="cursor">&nbsp;</span>
            </div>
          ) : null}
        </div>

        <label htmlFor="query">Ask a question:</label>
        <textarea
          id="query"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          rows={4}
          placeholder="Type your question here..."
        />

        <button onClick={submitQuery} disabled={loading || !query.trim()}>
          {loading ? 'Streaming answer...' : 'Ask Ollama'}
        </button>

        {error && <div className="toast error">{error}</div>}

        {documents.length > 0 && (
          <div className="result">
            <h3>Retrieved documents</h3>
            <ul>
              {documents.map((doc) => (
                <li key={doc.id}>
                  <strong>{doc.source}</strong>
                  <p>{doc.content.slice(0, 250)}{doc.content.length > 250 ? '...' : ''}</p>
                </li>
              ))}
            </ul>
          </div>
        )}
      </div>
    </div>
  );
}

const rootElement = document.getElementById('root');
if (rootElement) {
  createRoot(rootElement).render(<App />);
}

export default App;
