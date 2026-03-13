import { useState } from "react";
import { FaTimes, FaStar, FaPaperPlane } from "react-icons/fa";
import { submitReview } from "../../services/customerService";
import { showAlert } from "../../utils/sweetAlert";

const ReviewModal = ({ booking, onClose, onSuccess }) => {
  const [rating, setRating] = useState(0);
  const [hoveredRating, setHoveredRating] = useState(0);
  const [comment, setComment] = useState("");
  const [loading, setLoading] = useState(false);

  const labels = {
    1: "Poor",
    2: "Fair",
    3: "Good",
    4: "Very Good",
    5: "Excellent",
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (rating === 0) {
      showAlert.error(
        "Rating Required",
        "Please select a star rating before submitting.",
      );
      return;
    }
    setLoading(true);
    try {
      await submitReview({
        booking_id: booking.id,
        rating,
        comment,
        service_type: "shipping",
      });
      showAlert.success(
        "Review Submitted!",
        "Thank you for your feedback. It helps us improve our service.",
      );
      onSuccess?.();
      onClose();
    } catch (err) {
      showAlert.error(
        "Submission Failed",
        err.response?.data?.message ||
          "Failed to submit review. Please try again.",
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <div>
            <h2 className="text-xl font-bold text-gray-900">
              Rate Your Experience
            </h2>
            <p className="text-sm text-gray-500 mt-0.5">
              Booking #{booking.booking_reference || booking.id}
            </p>
          </div>
          <button
            onClick={onClose}
            className="p-2 hover:bg-gray-100 rounded-full transition-colors"
          >
            <FaTimes className="text-gray-500" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-6">
          {/* Star Rating */}
          <div className="text-center">
            <p className="text-sm font-medium text-gray-700 mb-3">
              How was your shipping experience?
            </p>
            <div className="flex justify-center gap-2">
              {[1, 2, 3, 4, 5].map((star) => (
                <button
                  key={star}
                  type="button"
                  onClick={() => setRating(star)}
                  onMouseEnter={() => setHoveredRating(star)}
                  onMouseLeave={() => setHoveredRating(0)}
                  className="p-1 transition-transform hover:scale-110"
                >
                  <FaStar
                    className={`text-4xl transition-colors ${
                      star <= (hoveredRating || rating)
                        ? "text-yellow-400"
                        : "text-gray-300"
                    }`}
                  />
                </button>
              ))}
            </div>
            {(hoveredRating || rating) > 0 && (
              <p className="text-sm font-semibold text-blue-600 mt-2">
                {labels[hoveredRating || rating]}
              </p>
            )}
          </div>

          {/* Comment */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Comments <span className="text-gray-400">(optional)</span>
            </label>
            <textarea
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              rows={4}
              placeholder="Tell us about your experience with the shipping process, communication, and delivery..."
              className="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm"
              maxLength={500}
            />
            <p className="text-xs text-gray-400 text-right mt-1">
              {comment.length}/500
            </p>
          </div>

          {/* Buttons */}
          <div className="flex gap-3">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading || rating === 0}
              className="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              {loading ? (
                <span className="animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5" />
              ) : (
                <>
                  <FaPaperPlane className="text-sm" />
                  Submit Review
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ReviewModal;
