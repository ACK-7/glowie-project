import React from 'react';
import { FaExclamationTriangle, FaSyncAlt } from 'react-icons/fa';

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error) {
    // Update state so the next render will show the fallback UI
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    // Log the error for debugging
    console.error('ErrorBoundary caught an error:', error, errorInfo);
    
    // Check if it's a React object rendering error
    const isObjectRenderError = error.message && (
      error.message.includes('Objects are not valid as a React child') ||
      error.message.includes('Cannot read properties of undefined') ||
      error.message.includes('Cannot read property')
    );
    
    this.setState({
      error,
      errorInfo,
      isObjectRenderError
    });
  }

  handleRetry = () => {
    this.setState({ hasError: false, error: null, errorInfo: null });
  };

  render() {
    if (this.state.hasError) {
      const { error, isObjectRenderError } = this.state;
      
      return (
        <div className="bg-red-900/20 border border-red-700/50 rounded-xl p-6 text-center">
          <FaExclamationTriangle className="text-red-400 text-4xl mx-auto mb-4" />
          <h2 className="text-red-400 text-lg font-semibold mb-2">
            {isObjectRenderError ? 'Data Rendering Error' : 'Something went wrong'}
          </h2>
          <p className="text-gray-400 mb-4">
            {isObjectRenderError 
              ? 'There was an issue displaying some data. This usually happens when complex objects are rendered as text.'
              : 'An unexpected error occurred while rendering this component.'
            }
          </p>
          
          {process.env.NODE_ENV === 'development' && error && (
            <details className="text-left bg-gray-800 rounded-lg p-4 mb-4">
              <summary className="text-red-400 cursor-pointer mb-2">Error Details</summary>
              <pre className="text-xs text-gray-300 overflow-auto">
                {error.toString()}
                {this.state.errorInfo && this.state.errorInfo.componentStack}
              </pre>
            </details>
          )}
          
          <div className="flex gap-3 justify-center">
            <button
              onClick={this.handleRetry}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
            >
              <FaSyncAlt />
              Try Again
            </button>
            
            {this.props.onError && (
              <button
                onClick={() => this.props.onError(error)}
                className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
              >
                Report Issue
              </button>
            )}
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

// Higher-order component to wrap components with error boundary
export const withErrorBoundary = (Component, errorFallback) => {
  return function WrappedComponent(props) {
    return (
      <ErrorBoundary fallback={errorFallback}>
        <Component {...props} />
      </ErrorBoundary>
    );
  };
};

// Hook to use error boundary in functional components
export const useErrorHandler = () => {
  const [error, setError] = React.useState(null);
  
  const resetError = () => setError(null);
  
  const handleError = React.useCallback((error) => {
    console.error('Error caught by useErrorHandler:', error);
    setError(error);
  }, []);
  
  React.useEffect(() => {
    if (error) {
      throw error;
    }
  }, [error]);
  
  return { handleError, resetError };
};

export default ErrorBoundary;