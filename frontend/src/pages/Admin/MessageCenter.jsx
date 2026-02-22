import React, { useState, useEffect } from 'react';
import { FaInbox, FaPaperPlane, FaUser, FaClock, FaSpinner } from 'react-icons/fa';
import { getMessages, sendMessage } from '../../services/adminService';

const MessageCenter = () => {
  const [selectedMessage, setSelectedMessage] = useState(null);
  const [messages, setMessages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [replyText, setReplyText] = useState('');
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchMessages();
  }, []);

  const fetchMessages = async () => {
    try {
      setLoading(true);
      const response = await getMessages();
      const data = response.data || response || [];
      setMessages(data);
      if (data.length > 0 && !selectedMessage) {
        setSelectedMessage(data[0]);
      }
      setError(null);
    } catch (err) {
      console.error('Failed to fetch messages:', err);
      setError('Failed to load messages.');
      setMessages([]);
    } finally {
      setLoading(false);
    }
  };

  const handleSendReply = async () => {
    if (!replyText.trim() || !selectedMessage) return;

    try {
      setSending(true);
      await sendMessage({
        message_id: selectedMessage.id,
        reply: replyText,
        customer_id: selectedMessage.customer_id
      });
      
      setReplyText('');
      fetchMessages(); // Refresh messages
    } catch (err) {
      console.error('Failed to send reply:', err);
      alert('Failed to send reply. Please try again.');
    } finally {
      setSending(false);
    }
  };

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-white mb-2">Message Center</h1>
        <p className="text-gray-400">Communicate with customers</p>
      </div>

      {loading ? (
        <div className="flex items-center justify-center py-12">
          <FaSpinner className="animate-spin text-blue-500 text-4xl" />
        </div>
      ) : error ? (
        <div className="bg-red-900/20 border border-red-700/50 rounded-xl p-6 text-center">
          <p className="text-red-400">{error}</p>
        </div>
      ) : (
        <div className="grid lg:grid-cols-3 gap-6">
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-800">
              <h2 className="text-white font-bold">
                Inbox ({messages.filter(m => !m.read || m.is_unread).length})
              </h2>
            </div>
            {messages.length === 0 ? (
              <div className="p-8 text-center text-gray-400">
                <FaInbox className="text-4xl mx-auto mb-4 opacity-50" />
                <p>No messages</p>
              </div>
            ) : (
              <div className="divide-y divide-gray-800">
                {messages.map((msg) => (
                  <div
                    key={msg.id}
                    onClick={() => setSelectedMessage(msg)}
                    className={`p-4 cursor-pointer transition ${
                      (msg.is_unread || msg.unread) ? 'bg-blue-900/10' : ''
                    } hover:bg-gray-800/50 ${selectedMessage?.id === msg.id ? 'bg-gray-800/50' : ''}`}
                  >
                    <div className="flex items-start gap-3">
                      <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <FaUser className="text-white text-sm" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between mb-1">
                          <p className={`font-semibold ${(msg.is_unread || msg.unread) ? 'text-white' : 'text-gray-300'}`}>
                            {msg.customer?.name || msg.from || msg.customer_name || 'Unknown'}
                          </p>
                          <span className="text-gray-500 text-xs">
                            {msg.created_at ? new Date(msg.created_at).toLocaleString() : msg.time || 'N/A'}
                          </span>
                        </div>
                        <p className="text-gray-400 text-sm mb-1">{msg.subject || msg.title || 'No subject'}</p>
                        <p className="text-gray-500 text-xs truncate">{msg.message || msg.preview || msg.body || ''}</p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="lg:col-span-2 bg-[#1a1f28] border border-gray-800 rounded-xl">
            {selectedMessage ? (
              <div className="flex flex-col h-full">
                <div className="px-6 py-4 border-b border-gray-800">
                  <h3 className="text-white font-bold mb-1">{selectedMessage.subject || selectedMessage.title || 'No subject'}</h3>
                  <p className="text-gray-400 text-sm">
                    From: {selectedMessage.customer?.name || selectedMessage.from || selectedMessage.customer_name || 'Unknown'}
                  </p>
                </div>
                <div className="flex-1 p-6">
                  <p className="text-gray-300 whitespace-pre-wrap">
                    {selectedMessage.message || selectedMessage.body || selectedMessage.preview || 'No message content'}
                  </p>
                </div>
                <div className="px-6 py-4 border-t border-gray-800">
                  <textarea
                    placeholder="Type your reply..."
                    value={replyText}
                    onChange={(e) => setReplyText(e.target.value)}
                    className="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:outline-none focus:border-blue-500 mb-3"
                    rows="3"
                  ></textarea>
                  <button 
                    onClick={handleSendReply}
                    disabled={!replyText.trim() || sending}
                    className="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold px-6 py-2 rounded-lg flex items-center gap-2 transition"
                  >
                    {sending ? (
                      <>
                        <FaSpinner className="animate-spin" />
                        Sending...
                      </>
                    ) : (
                      <>
                        <FaPaperPlane />
                        Send Reply
                      </>
                    )}
                  </button>
                </div>
              </div>
            ) : (
              <div className="flex items-center justify-center h-full p-12">
                <div className="text-center">
                  <FaInbox className="text-6xl text-gray-700 mb-4 mx-auto" />
                  <p className="text-gray-400">Select a message to view</p>
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default MessageCenter;
