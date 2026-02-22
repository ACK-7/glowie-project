import React, { useState, useRef } from 'react';
import { 
  FaUpload, 
  FaFileAlt, 
  FaTimes, 
  FaCheck,
  FaSpinner,
  FaCloudUploadAlt,
  FaExclamationTriangle
} from 'react-icons/fa';
import { uploadCustomerDocument } from '../../services/customerService';
import { showAlert } from '../../utils/sweetAlert';

const DocumentUpload = ({ onUploadSuccess, bookingId = null }) => {
  const [dragActive, setDragActive] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [selectedFiles, setSelectedFiles] = useState([]);
  const fileInputRef = useRef(null);

  const documentTypes = [
    { value: 'passport', label: 'Passport' },
    { value: 'license', label: 'Driving License' },
    { value: 'invoice', label: 'Purchase Invoice' },
    { value: 'insurance', label: 'Insurance Certificate' },
    { value: 'customs', label: 'Customs Declaration' },
    { value: 'other', label: 'Other Document' }
  ];

  const handleDrag = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const handleDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFiles(e.dataTransfer.files);
    }
  };

  const handleChange = (e) => {
    e.preventDefault();
    if (e.target.files && e.target.files[0]) {
      handleFiles(e.target.files);
    }
  };

  const handleFiles = (files) => {
    const fileArray = Array.from(files);
    const validFiles = fileArray.filter(file => {
      // Check file size (max 10MB)
      if (file.size > 10 * 1024 * 1024) {
        showAlert('Error', `File ${file.name} is too large. Maximum size is 10MB.`, 'error');
        return false;
      }
      
      // Check file type
      const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf', 'image/gif'];
      if (!allowedTypes.includes(file.type)) {
        showAlert('Error', `File ${file.name} has an unsupported format. Please use JPG, PNG, PDF, or GIF.`, 'error');
        return false;
      }
      
      return true;
    });

    const newFiles = validFiles.map(file => ({
      file,
      id: Date.now() + Math.random(),
      documentType: 'other',
      description: ''
    }));

    setSelectedFiles(prev => [...prev, ...newFiles]);
  };

  const removeFile = (fileId) => {
    setSelectedFiles(prev => prev.filter(f => f.id !== fileId));
  };

  const updateFileType = (fileId, documentType) => {
    setSelectedFiles(prev => 
      prev.map(f => f.id === fileId ? { ...f, documentType } : f)
    );
  };

  const updateFileDescription = (fileId, description) => {
    setSelectedFiles(prev => 
      prev.map(f => f.id === fileId ? { ...f, description } : f)
    );
  };

  const uploadFiles = async () => {
    if (selectedFiles.length === 0) {
      showAlert('Error', 'Please select at least one file to upload.', 'error');
      return;
    }

    setUploading(true);
    let successCount = 0;
    let errorCount = 0;

    for (const fileData of selectedFiles) {
      try {
        const formData = new FormData();
        formData.append('file', fileData.file);
        formData.append('document_type', fileData.documentType);
        formData.append('description', fileData.description || '');
        
        if (bookingId) {
          formData.append('booking_id', bookingId);
        }

        await uploadCustomerDocument(formData);
        successCount++;
      } catch (error) {
        console.error('Upload failed for file:', fileData.file.name, error);
        errorCount++;
      }
    }

    setUploading(false);

    if (successCount > 0) {
      showAlert('Success', `Successfully uploaded ${successCount} document(s).`, 'success');
      setSelectedFiles([]);
      if (onUploadSuccess) {
        onUploadSuccess();
      }
    }

    if (errorCount > 0) {
      showAlert('Warning', `${errorCount} document(s) failed to upload. Please try again.`, 'warning');
    }
  };

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  return (
    <div className="space-y-6">
      {/* Upload Area */}
      <div
        className={`relative border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
          dragActive
            ? 'border-blue-500 bg-blue-50'
            : 'border-gray-300 hover:border-gray-400'
        }`}
        onDragEnter={handleDrag}
        onDragLeave={handleDrag}
        onDragOver={handleDrag}
        onDrop={handleDrop}
      >
        <input
          ref={fileInputRef}
          type="file"
          multiple
          accept=".jpg,.jpeg,.png,.pdf,.gif"
          onChange={handleChange}
          className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
        />
        
        <div className="space-y-4">
          <FaCloudUploadAlt className="mx-auto text-4xl text-gray-400" />
          <div>
            <p className="text-lg font-medium text-gray-900">
              Drop files here or click to browse
            </p>
            <p className="text-sm text-gray-500 mt-1">
              Supports JPG, PNG, PDF, GIF up to 10MB each
            </p>
          </div>
          <button
            type="button"
            onClick={() => fileInputRef.current?.click()}
            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <FaUpload className="mr-2" />
            Select Files
          </button>
        </div>
      </div>

      {/* Selected Files */}
      {selectedFiles.length > 0 && (
        <div className="space-y-4">
          <h3 className="text-lg font-medium text-gray-900">Selected Files</h3>
          
          <div className="space-y-3">
            {selectedFiles.map((fileData) => (
              <div key={fileData.id} className="border rounded-lg p-4 bg-gray-50">
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center space-x-3">
                    <FaFileAlt className="text-blue-500 text-xl" />
                    <div>
                      <p className="font-medium text-gray-900">{fileData.file.name}</p>
                      <p className="text-sm text-gray-500">{formatFileSize(fileData.file.size)}</p>
                    </div>
                  </div>
                  <button
                    onClick={() => removeFile(fileData.id)}
                    className="text-red-500 hover:text-red-700 p-1"
                  >
                    <FaTimes />
                  </button>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Document Type *
                    </label>
                    <select
                      value={fileData.documentType}
                      onChange={(e) => updateFileType(fileData.id, e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      {documentTypes.map((type) => (
                        <option key={type.value} value={type.value}>
                          {type.label}
                        </option>
                      ))}
                    </select>
                  </div>
                  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Description (Optional)
                    </label>
                    <input
                      type="text"
                      value={fileData.description}
                      onChange={(e) => updateFileDescription(fileData.id, e.target.value)}
                      placeholder="Brief description..."
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Upload Button */}
          <div className="flex justify-end">
            <button
              onClick={uploadFiles}
              disabled={uploading || selectedFiles.length === 0}
              className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {uploading ? (
                <>
                  <FaSpinner className="animate-spin mr-2" />
                  Uploading...
                </>
              ) : (
                <>
                  <FaUpload className="mr-2" />
                  Upload {selectedFiles.length} File{selectedFiles.length !== 1 ? 's' : ''}
                </>
              )}
            </button>
          </div>
        </div>
      )}

      {/* Upload Guidelines */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div className="flex items-start">
          <FaExclamationTriangle className="text-blue-500 mt-0.5 mr-3 flex-shrink-0" />
          <div className="text-sm text-blue-800">
            <p className="font-medium mb-2">Document Upload Guidelines:</p>
            <ul className="list-disc list-inside space-y-1">
              <li>Ensure documents are clear and readable</li>
              <li>Upload original documents, not photocopies when possible</li>
              <li>Maximum file size: 10MB per document</li>
              <li>Supported formats: JPG, PNG, PDF, GIF</li>
              <li>Documents will be reviewed within 24-48 hours</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DocumentUpload;