import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import App from '../app';

describe('App streaming behavior', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('renders streamed answer content from a ReadableStream response', async () => {
    const answer = 'Streamed AI answer for the user.';
    const payload = JSON.stringify({ answer, documents: [], session_id: 'session-abc' });
    const encoder = new TextEncoder();

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      body: new ReadableStream<Uint8Array>({
        start(controller) {
          const firstChunk = encoder.encode(payload.slice(0, 20));
          const secondChunk = encoder.encode(payload.slice(20));
          controller.enqueue(firstChunk);
          controller.enqueue(secondChunk);
          controller.close();
        },
      }),
      json: vi.fn(),
    });

    render(<App />);

    const textarea = screen.getByRole('textbox', { name: /ask a question/i });
    await userEvent.type(textarea, 'What is local rag?');

    const button = screen.getByRole('button', { name: /ask ollama/i });
    await userEvent.click(button);

    await waitFor(() => {
      expect(screen.getByText(/streamed ai answer/i)).toBeInTheDocument();
    }, { timeout: 1500 });
  });
});
