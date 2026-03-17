import { useState, useRef, useEffect } from "react";
import { FaRobot, FaTimes, FaPaperPlane, FaSpinner } from "react-icons/fa";
import axios from "axios";

const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api";

const STORAGE_KEY = "glowie_chat_history";
const SESSION_TTL = 60 * 60 * 1000; // 1 hour

const applyInlineFormatting = (text) => {
  if (!text) return text;
  const segments = text.split(/(\*\*[^*]+\*\*|_[^_]+_|`[^`]+`)/g);
  return segments.map((seg, i) => {
    if (seg.startsWith("**") && seg.endsWith("**")) {
      return <strong key={i}>{seg.slice(2, -2)}</strong>;
    }
    if (seg.startsWith("_") && seg.endsWith("_")) {
      return <em key={i}>{seg.slice(1, -1)}</em>;
    }
    if (seg.startsWith("`") && seg.endsWith("`")) {
      return (
        <code
          key={i}
          className="px-1 py-0.5 rounded bg-gray-100 border border-gray-200 text-[0.85em]"
        >
          {seg.slice(1, -1)}
        </code>
      );
    }
    return <span key={i}>{seg}</span>;
  });
};

const tryParseJson = (value) => {
  try {
    const parsed = JSON.parse(value);
    return typeof parsed === "object" ? parsed : null;
  } catch {
    return null;
  }
};

const renderMessageContent = (text) => {
  if (!text) return null;

  const parsedJson = tryParseJson(text);
  if (parsedJson) {
    return (
      <pre className="text-xs font-mono whitespace-pre-wrap break-words bg-gray-50 border border-gray-200 rounded-lg p-3">
        {JSON.stringify(parsedJson, null, 2)}
      </pre>
    );
  }

  const listMatchers = [/^[-*•]\s+/, /^\d+[\).\s]/];

  const paragraphs = text.split(/\n\s*\n/).filter(Boolean);

  return paragraphs.map((block, idx) => {
    const lines = block.split("\n").filter(Boolean);

    const isTable =
      lines.length >= 2 &&
      /^\s*\|.*\|\s*$/.test(lines[0]) &&
      /^\s*\|?\s*[-:]+\s*\|/.test(lines[1]);

    if (isTable) {
      const rows = lines.map((line) =>
        line
          .split("|")
          .map((c) => c.trim())
          .filter((c) => c),
      );
      const [header = [], ...body] = rows;
      return (
        <div key={idx} className="overflow-x-auto">
          <table className="min-w-full border border-gray-200 text-xs rounded-lg overflow-hidden">
            <thead className="bg-gray-50">
              <tr>
                {header.map((cell, i) => (
                  <th
                    key={i}
                    className="px-2 py-2 text-left font-semibold border-b border-gray-200"
                  >
                    {applyInlineFormatting(cell)}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {body.map((row, rIdx) => (
                <tr key={rIdx} className="odd:bg-white even:bg-gray-50">
                  {row.map((cell, cIdx) => (
                    <td key={cIdx} className="px-2 py-2 align-top border-b border-gray-200">
                      {applyInlineFormatting(cell)}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      );
    }

    const headingMatch = lines[0].match(/^(#{2,6})\s+(.*)/);
    if (headingMatch) {
      const level = headingMatch[1].length;
      const content = headingMatch[2];
      const HeadingTag = `h${Math.min(level, 6)}`;
      return (
        <HeadingTag key={idx} className="font-semibold text-gray-800 text-sm mt-1 mb-1">
          {applyInlineFormatting(content)}
        </HeadingTag>
      );
    }

    const isList =
      lines.length > 1 &&
      lines.every((line) => listMatchers.some((re) => re.test(line.trim())));

    if (isList) {
      return (
        <ul key={idx} className="list-disc list-inside space-y-1">
          {lines.map((line, i) => {
            const cleaned = line
              .replace(/^[-*•]\s+/, "")
              .replace(/^\d+[\).\s]+/, "");
            return <li key={i}>{applyInlineFormatting(cleaned)}</li>;
          })}
        </ul>
      );
    }

    const lineFragments = lines.map((line, i) => (
      <span key={i}>
        {applyInlineFormatting(line)}
        {i < lines.length - 1 ? <br /> : null}
      </span>
    ));

    return (
      <p key={idx} className="mb-2 last:mb-0">
        {lineFragments}
      </p>
    );
  });
};

const loadChatHistory = () => {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (!saved) return null;
    const { messages, savedAt } = JSON.parse(saved);
    if (Date.now() - savedAt > SESSION_TTL) {
      localStorage.removeItem(STORAGE_KEY);
      return null;
    }
    return messages.map((m) => ({ ...m, timestamp: new Date(m.timestamp) }));
  } catch {
    return null;
  }
};

const saveChatHistory = (messages) => {
  try {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({ messages, savedAt: Date.now() }),
    );
  } catch {}
};

const AIChatbot = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState(() => {
    return (
      loadChatHistory() || [
        {
          type: "ai",
          text: "Hello! 👋 I'm your AI shipping assistant. How can I help you today?",
          timestamp: new Date(),
        },
      ]
    );
  });
  const [inputMessage, setInputMessage] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const messagesEndRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const handleSendMessage = async (e) => {
    e.preventDefault();
    if (!inputMessage.trim() || isLoading) return;

    const userMessage = inputMessage.trim();
    setInputMessage("");

    // Add user message
    setMessages((prev) => {
      const updated = [
        ...prev,
        { type: "user", text: userMessage, timestamp: new Date() },
      ];
      saveChatHistory(updated);
      return updated;
    });

    setIsLoading(true);

    try {
      const response = await axios.post(`${API_BASE_URL}/chatbot`, {
        query: userMessage,
        context: "marketing_website",
      });

      // Add AI response
      const aiResponse =
        response.data.data?.response ||
        response.data.response ||
        response.data.message ||
        "I apologize, but I couldn't generate a response.";

      setMessages((prev) => {
        const updated = [
          ...prev,
          { type: "ai", text: aiResponse, timestamp: new Date() },
        ];
        saveChatHistory(updated);
        return updated;
      });
    } catch (error) {
      console.error("Chatbot error:", error);
      setMessages((prev) => {
        const updated = [
          ...prev,
          {
            type: "ai",
            text: "I apologize, but I'm having trouble connecting right now. Please try again or contact us directly.",
            timestamp: new Date(),
          },
        ];
        saveChatHistory(updated);
        return updated;
      });
    } finally {
      setIsLoading(false);
    }
  };

  const quickQuestions = [
    "How much does shipping cost?",
    "How long does shipping take?",
    "What documents do I need?",
    "Can I track my shipment?",
  ];

  const handleQuickQuestion = (question) => {
    setInputMessage(question);
  };

  return (
    <>
      {/* Chat Button */}
      {!isOpen && (
        <button
          onClick={() => setIsOpen(true)}
          className="fixed bottom-6 right-6 z-50 bg-gradient-to-r from-blue-600 to-purple-600 text-white p-4 rounded-full shadow-2xl hover:shadow-3xl transform hover:scale-110 transition-all duration-300 flex items-center gap-2 group"
          aria-label="Open AI Chat"
        >
          <FaRobot className="text-2xl" />
          <span className="hidden group-hover:inline-block text-sm font-medium pr-2">
            Need Help?
          </span>
        </button>
      )}

      {/* Chat Window */}
      {isOpen && (
        <div className="fixed bottom-6 right-6 z-50 w-96 h-[600px] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-200">
          {/* Header */}
          <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-4 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                <FaRobot className="text-xl" />
              </div>
              <div>
                <h3 className="font-bold">AI Assistant</h3>
                <p className="text-xs text-white/80">Always here to help</p>
              </div>
            </div>
            <button
              onClick={() => setIsOpen(false)}
              className="text-white/80 hover:text-white transition-colors"
              aria-label="Close chat"
            >
              <FaTimes className="text-xl" />
            </button>
          </div>

          {/* Messages */}
          <div className="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
            {messages.map((message, index) => (
              <div
                key={index}
                className={`flex ${message.type === "user" ? "justify-end" : "justify-start"}`}
              >
                <div
                  className={`max-w-[80%] rounded-2xl px-4 py-3 ${
                    message.type === "user"
                      ? "bg-gradient-to-r from-blue-600 to-purple-600 text-white"
                      : "bg-white text-gray-800 shadow-sm border border-gray-200"
                  }`}
                >
                  <div className="text-sm leading-relaxed space-y-2">
                    {renderMessageContent(message.text)}
                  </div>
                  <p
                    className={`text-xs mt-1 ${
                      message.type === "user"
                        ? "text-white/70"
                        : "text-gray-500"
                    }`}
                  >
                    {message.timestamp.toLocaleTimeString([], {
                      hour: "2-digit",
                      minute: "2-digit",
                    })}
                  </p>
                </div>
              </div>
            ))}

            {isLoading && (
              <div className="flex justify-start">
                <div className="bg-white text-gray-800 rounded-2xl px-4 py-3 shadow-sm border border-gray-200">
                  <FaSpinner className="animate-spin text-blue-600" />
                </div>
              </div>
            )}

            <div ref={messagesEndRef} />
          </div>

          {/* Quick Questions */}
          {messages.length === 1 && (
            <div className="px-4 py-2 bg-white border-t border-gray-200">
              <p className="text-xs text-gray-600 mb-2">Quick questions:</p>
              <div className="flex flex-wrap gap-2">
                {quickQuestions.map((question, index) => (
                  <button
                    key={index}
                    onClick={() => handleQuickQuestion(question)}
                    className="text-xs bg-blue-50 text-blue-700 px-3 py-1.5 rounded-full hover:bg-blue-100 transition-colors"
                  >
                    {question}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Input */}
          <form
            onSubmit={handleSendMessage}
            className="p-4 bg-white border-t border-gray-200"
          >
            <div className="flex gap-2">
              <input
                type="text"
                value={inputMessage}
                onChange={(e) => setInputMessage(e.target.value)}
                placeholder="Type your message..."
                className="flex-1 px-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                disabled={isLoading}
              />
              <button
                type="submit"
                disabled={!inputMessage.trim() || isLoading}
                className="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-3 rounded-full hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                aria-label="Send message"
              >
                <FaPaperPlane />
              </button>
            </div>
          </form>
        </div>
      )}
    </>
  );
};

export default AIChatbot;
