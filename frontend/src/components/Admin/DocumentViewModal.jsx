import { useState } from 'react';
import { 
  FaTimes, 
  FaFileAlt, 
  FaCheck, 
  FaBan, 
  FaDownload,
  FaEye,
  FaCalendarAlt,
  FaUser,
  FaSpinner,
  FaExclamationTriangle
} from 'react-icons/fa';
import { approveDocument, rejectDocument, downloadDocument } from '../../services/adminService';
import { showAlert, showConfirm } from '../../utils/sweetAlert';
import { safeRender, safeDate } from '../../utils/safeRender';
import ErrorBoundary from '../Common/ErrorBoundary';

const DocumentViewModal = ({ document, onClose, onUpdate }) => {
  const [loading, setLoading] = useState(false);
  const [rejectionReason, setRejectionReason] = useState('');
  const [showRejectForm, setShowRejectForm] = useState(false);
  const [approvalNotes, setApprovalNotes] = useState('');
  const [showApprovalForm, setShowApprovalForm] = useState(false);

  const handleApprove = async () => {
    if (document.status !== 'pending') {
      showAlert.error('Error', 'Only pending documents can be approved');
      return;
    }

    try {
      setLoading(true);
      showAlert.loading('Approving...', 'Processing document approval...');
      
      await approveDocument(document.id, approvalNotes);
      
      showAlert.close();
      showAlert.success('Success!', 'Document approved successfully!');
      
      if (onUpdate) onUpdate();
      onClose();
    } catch (error) {
      showAlert.close();
      console.error('Approval failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to approve document';
      showAlert.error('Approval Failed', errorMessage);
    } finally {
      setLoading(false);
      setShowApprovalForm(false);
      setApprovalNotes('');
    }
  };

  const handleReject = async () => {
    if (document.status !== 'pending') {
      showAlert.error('Error', 'Only pending documents can be rejected');
      return;
    }

    if (!rejectionReason.trim()) {
      showAlert.error('Error', 'Please provide a reason for rejection');
      return;
    }

    try {
      setLoading(true);
      showAlert.loading('Rejecting...', 'Processing document rejection...');
      
      await rejectDocument(document.id, rejectionReason);
      
      showAlert.close();
      showAlert.success('Success!', 'Document rejected successfully!');
      
      if (onUpdate) onUpdate();
      onClose();
    } catch (error) {
      showAlert.close();
      console.error('Rejection failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to reject document';
      showAlert.error('Rejection Failed', errorMessage);
    } finally {
      setLoading(false);
      setShowRejectForm(false);
      setRejectionReason('');
    }
  };

  const handleDownload = async () => {
    try {
      showAlert.loading('Downloading...', 'Preparing document download...');
      await downloadDocument(document.id);
      showAlert.close();
      showAlert.success('Success!', 'Document downloaded successfully!');
    } catch (error) {
      showAlert.close();
      console.error('Download failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to download document';
      showAlert.error('Download Failed', errorMessage);
    }
  };

  const getStatusBadge = (status) => {
    const config = {
      verified: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Verified', icon: FaCheck },
      approved: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Approved', icon: FaCheck },
      pending: { bg: 'bg-yellow-900/30', text: 'text-yellow-400', label: 'Pending', icon: FaSpinner },
      rejected: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Rejected', icon: FaBan },
      requires_revision: { bg: 'bg-orange-900/30', text: 'text-orange-400', label: 'Needs Revision', icon: FaExclamationTriangle }
    };
    
    const c = config[status] || config.pending;
    const IconComponent = c.icon;
    
    return (
      <span className={`${c.bg} ${c.text} px-3 py-1 rounded-full text-sm font-semibold flex items-center gap-2`}>
        <IconComponent className="text-sm" />
        {c.label}
      </span>
    );
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const formatFileSize = (bytes) => {
    if (!bytes) return 'N/A';
    const mb = bytes / (1024 * 1024);
    return `${mb.toFixed(2)} MB`;
  };

  const getDocumentTypeLabel = (type) => {
    const labels = {
      passport: 'Passport',
      license: 'Driving License',
      invoice: 'Purchase Invoice',
      insurance: 'Insurance Certificate',
      customs: 'Customs Declaration',
      other: 'Other Document'
    };
    return labels[type] || 'Unknown Document';
  };

  if (!document) return null;

  return (
    <ErrorBoundary>
      <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-[#1a1f28] border border-gray-800 rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-800">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 bg-blue-600/20 rounded-lg flex items-center justify-center">
              <FaFileAlt className="text-blue-400 text-xl" />
            </div>
            <div>
              <h2 className="text-xl font-bold text-white">
                {getDocumentTypeLabel(document.document_type)}
              </h2>
              <p className="text-gray-400">
                {safeRender(document.file_name, 'Document Details')}
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
          >
            <FaTimes />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 space-y-6">
          {/* Status and Actions */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              {getStatusBadge(document.status)}
              {document.expiry_date && (
                <div className="flex items-center gap-2 text-sm text-gray-400">
                  <FaCalendarAlt />
                  <span>Expires: {safeDate(document.expiry_date)}</span>
                </div>
              )}
            </div>
            
            <div className="flex items-center gap-2">
              <button
                onClick={handleDownload}
                className="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors flex items-center gap-2"
              >
                <FaDownload />
                Download
              </button>
              
              {document.status === 'pending' && (
                <>
                  <button
                    onClick={() => setShowApprovalForm(true)}
                    disabled={loading}
                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2 disabled:opacity-50"
                  >
                    <FaCheck />
                    Approve
                  </button>
                  <button
                    onClick={() => setShowRejectForm(true)}
                    disabled={loading}
                    className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2 disabled:opacity-50"
                  >
                    <FaBan />
                    Reject
                  </button>
                </>
              )}
            </div>
          </div>

          {/* Document Information */}
          <div className="grid md:grid-cols-2 gap-6">
            <div className="space-y-4">
              <h3 className="text-lg font-semibold text-white">Document Information</h3>
              
              <div className="space-y-3">
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">Document Type</label>
                  <p className="text-white">{getDocumentTypeLabel(document.document_type)}</p>
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">File Name</label>
                  <p className="text-white">{safeRender(document.file_name)}</p>
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">File Size</label>
                  <p className="text-white">{formatFileSize(document.file_size)}</p>
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">File Type</label>
                  <p className="text-white">{safeRender(document.mime_type)}</p>
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">Upload Date</label>
                  <p className="text-white">{safeDate(document.created_at)}</p>
                </div>
              </div>
            </div>

            <div className="space-y-4">
              <h3 className="text-lg font-semibold text-white">Customer & Booking</h3>
              
              <div className="space-y-3">
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">Customer</label>
                  <p className="text-white">
                    {document.customer?.first_name && document.customer?.last_name 
                      ? `${safeRender(document.customer.first_name)} ${safeRender(document.customer.last_name)}`
                      : safeRender(document.customer?.name, 'Unknown Customer')
                    }
                  </p>
                  {document.customer?.email && (
                    <p className="text-gray-400 text-sm">{safeRender(document.customer.email)}</p>
                  )}
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">Booking Reference</label>
                  <p className="text-white">
                    {safeRender(
                      document.booking?.booking_reference || 
                      document.booking?.reference_number || 
                      (document.booking_id ? `BK-${document.booking_id}` : null)
                    )}
                  </p>
                </div>
                
                {document.verified_by && (
                  <div>
                    <label className="block text-sm font-medium text-gray-400 mb-1">Verified By</label>
                    <div className="flex items-center gap-2">
                      <FaUser className="text-gray-400" />
                      <span className="text-white">Admin User</span>
                    </div>
                    <p className="text-gray-400 text-sm">{safeDate(document.verified_at)}</p>
                  </div>
                )}
                
                {document.rejection_reason && (
                  <div>
                    <label className="block text-sm font-medium text-gray-400 mb-1">Rejection Reason</label>
                    <p className="text-red-400">{safeRender(document.rejection_reason)}</p>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Document Preview Placeholder */}
          <div className="border border-gray-700 rounded-lg p-6 text-center">
            <FaEye className="text-gray-400 text-4xl mx-auto mb-4" />
            <p className="text-gray-400 mb-2">Document Preview</p>
            <p className="text-gray-500 text-sm">
              Preview functionality will be available in a future update
            </p>
          </div>

          {/* Approval Form */}
          {showApprovalForm && (
            <div className="bg-green-900/20 border border-green-700/50 rounded-lg p-4">
              <h4 className="text-green-400 font-semibold mb-3">Approve Document</h4>
              <div className="space-y-3">
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-2">
                    Approval Notes (Optional)
                  </label>
                  <textarea
                    value={approvalNotes}
                    onChange={(e) => setApprovalNotes(e.target.value)}
                    className="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-green-500"
                    rows="3"
                    placeholder="Add any notes about the approval..."
                  />
                </div>
                <div className="flex gap-3">
                  <button
                    onClick={handleApprove}
                    disabled={loading}
                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
                  >
                    {loading ? 'Approving...' : 'Confirm Approval'}
                  </button>
                  <button
                    onClick={() => {
                      setShowApprovalForm(false);
                      setApprovalNotes('');
                    }}
                    className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* Rejection Form */}
          {showRejectForm && (
            <div className="bg-red-900/20 border border-red-700/50 rounded-lg p-4">
              <h4 className="text-red-400 font-semibold mb-3">Reject Document</h4>
              <div className="space-y-3">
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-2">
                    Rejection Reason *
                  </label>
                  <textarea
                    value={rejectionReason}
                    onChange={(e) => setRejectionReason(e.target.value)}
                    className="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-red-500"
                    rows="3"
                    placeholder="Please provide a clear reason for rejection..."
                    required
                  />
                </div>
                <div className="flex gap-3">
                  <button
                    onClick={handleReject}
                    disabled={loading || !rejectionReason.trim()}
                    className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                  >
                    {loading ? 'Rejecting...' : 'Confirm Rejection'}
                  </button>
                  <button
                    onClick={() => {
                      setShowRejectForm(false);
                      setRejectionReason('');
                    }}
                    className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
    </ErrorBoundary>
  );
};

export default DocumentViewModal;