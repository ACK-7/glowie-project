import { useState, useRef, useEffect } from "react";
import {
  FaRobot,
  FaTimes,
  FaPaperPlane,
  FaSpinner,
  FaChartBar,
  FaLightbulb,
  FaBrain,
} from "react-icons/fa";
import axios from "axios";

const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api";

const QUICK_PROMPTS = [
  {
    label: "Revenue Summary",
    icon: <FaChartBar />,
    prompt:
      "Give me a summary of our current revenue, pending payments, and financial health.",
  },
  {
    label: "Delayed Shipments",
    icon: <FaLightbulb />,
    prompt:
      "What shipments are currently delayed and what actions should we take?",
  },
  {
    label: "Operational Insights",
    icon: <FaBrain />,
    prompt:
      "Analyze our recent operations and suggest improvements for efficiency.",
  },
];

const AdminAIAssistant = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState([
    {
      type: "ai",
      text: "Hello Admin! 🤖 I'm your AI operations assistant. I can help you with:\n\n• **Revenue & financial reports**\n• **Shipment delay analysis**\n• **Operational insights & predictions**\n• **Customer analytics**\n\nAsk me anything or use a quick prompt below!",
      timestamp: new Date(),
    },
  ]);
  const [inputMessage, setInputMessage] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const messagesEndRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const sendMessage = async (text) => {
    if (!text.trim() || isLoading) return;

    const userMsg = { type: "user", text: text.trim(), timestamp: new Date() };
    setMessages((prev) => [...prev, userMsg]);
    setInputMessage("");
    setIsLoading(true);

    try {
      const token = localStorage.getItem("admin_token");
      const response = await axios.post(
        `${API_BASE_URL}/admin/ai-assistant`,
        {
          message: text.trim(),
          context: "admin_dashboard",
        },
        {
          headers: token ? { Authorization: `Bearer ${token}` } : {},
        },
      );

      const aiText =
        response.data?.data?.response ||
        response.data?.data?.message ||
        response.data?.message ||
        "I've processed your request. Let me know if you need anything else.";

      setMessages((prev) => [
        ...prev,
        { type: "ai", text: aiText, timestamp: new Date() },
      ]);
    } catch (error) {
      // Fallback: use the public chatbot endpoint
      try {
        const response = await axios.post(`${API_BASE_URL}/chatbot`, {
          message: `[ADMIN CONTEXT] ${text.trim()}`,
        });

        const aiText =
          response.data?.data?.response ||
          response.data?.data?.message ||
          response.data?.message ||
          "I've processed your request.";

        setMessages((prev) => [
          ...prev,
          { type: "ai", text: aiText, timestamp: new Date() },
        ]);
      } catch {
        setMessages((prev) => [
          ...prev,
          {
            type: "ai",
            text: "I'm having trouble connecting right now. Please try again in a moment.",
            timestamp: new Date(),
            isError: true,
          },
        ]);
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    sendMessage(inputMessage);
  };

  const formatMessage = (text) => {
    return text
      .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
      .replace(/\n/g, "<br/>");
  };

  if (!isOpen) {
    return (
      <button
        onClick={() => setIsOpen(true)}
        className="fixed bottom-6 right-6 z-50 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-full p-4 shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 group"
        title="AI Admin Assistant"
      >
        <FaBrain className="text-xl" />
        <span className="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse" />
        <span className="absolute bottom-full right-0 mb-2 px-3 py-1 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
          AI Admin Assistant
        </span>
      </button>
    );
  }

  return (
    <div className="fixed bottom-6 right-6 z-50 w-96 h-[550px] bg-[#1a1f2e] border border-gray-700 rounded-2xl shadow-2xl flex flex-col overflow-hidden">
      {/* Header */}
      <div className="bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-3 flex items-center justify-between">
        <div className="flex items-center gap-2">
          <FaBrain className="text-white text-lg" />
          <div>
            <h3 className="text-white font-semibold text-sm">
              AI Admin Assistant
            </h3>
            <p className="text-purple-200 text-xs">Powered by Mistral AI</p>
          </div>
        </div>
        <button
          onClick={() => setIsOpen(false)}
          className="text-white/80 hover:text-white transition-colors"
        >
          <FaTimes />
        </button>
      </div>

      {/* Messages */}
      <div className="flex-1 overflow-y-auto p-4 space-y-3">
        {messages.map((msg, i) => (
          <div
            key={i}
            className={`flex ${msg.type === "user" ? "justify-end" : "justify-start"}`}
          >
            <div
              className={`max-w-[85%] rounded-xl px-3 py-2 text-sm ${
                msg.type === "user"
                  ? "bg-purple-600 text-white"
                  : msg.isError
                    ? "bg-red-900/50 text-red-200 border border-red-700"
                    : "bg-gray-800 text-gray-200"
              }`}
            >
              {msg.type === "ai" && (
                <div className="flex items-center gap-1 mb-1">
                  <FaRobot className="text-purple-400 text-xs" />
                  <span className="text-purple-400 text-xs font-semibold">
                    AI Assistant
                  </span>
                </div>
              )}
              <div
                dangerouslySetInnerHTML={{
                  __html: formatMessage(msg.text),
                }}
              />
            </div>
          </div>
        ))}

        {isLoading && (
          <div className="flex justify-start">
            <div className="bg-gray-800 rounded-xl px-4 py-3 text-gray-400 text-sm flex items-center gap-2">
              <FaSpinner className="animate-spin" />
              Analyzing...
            </div>
          </div>
        )}

        <div ref={messagesEndRef} />
      </div>

      {/* Quick Prompts */}
      {messages.length <= 1 && (
        <div className="px-4 pb-2">
          <p className="text-gray-500 text-xs mb-2">Quick prompts:</p>
          <div className="flex flex-wrap gap-1">
            {QUICK_PROMPTS.map((qp, i) => (
              <button
                key={i}
                onClick={() => sendMessage(qp.prompt)}
                className="flex items-center gap-1 px-2 py-1 bg-gray-800 hover:bg-purple-900/50 text-gray-300 hover:text-purple-300 rounded-lg text-xs transition-colors border border-gray-700 hover:border-purple-600"
              >
                {qp.icon}
                {qp.label}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Input */}
      <form
        onSubmit={handleSubmit}
        className="p-3 border-t border-gray-700 flex gap-2"
      >
        <input
          type="text"
          value={inputMessage}
          onChange={(e) => setInputMessage(e.target.value)}
          placeholder="Ask about operations, revenue, shipments..."
          className="flex-1 bg-gray-800 text-white text-sm rounded-xl px-4 py-2 border border-gray-600 focus:outline-none focus:border-purple-500 placeholder-gray-500"
          disabled={isLoading}
        />
        <button
          type="submit"
          disabled={!inputMessage.trim() || isLoading}
          className="bg-purple-600 text-white rounded-xl px-3 py-2 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <FaPaperPlane className="text-sm" />
        </button>
      </form>
    </div>
  );
};

export default AdminAIAssistant;
