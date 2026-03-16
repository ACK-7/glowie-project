import React, { useRef } from "react";
import {
  FaTimes,
  FaPrint,
  FaFileInvoiceDollar,
  FaDownload,
} from "react-icons/fa";

const InvoiceModal = ({ payment, onClose, type = "invoice" }) => {
  const printRef = useRef();

  if (!payment) return null;

  const isReceipt = type === "receipt";
  const title = isReceipt ? "Payment Receipt" : "Shipping Invoice";
  const docNumber = isReceipt
    ? `RCT-${payment.payment_reference || payment.id}`
    : `INV-${payment.booking?.booking_reference || payment.id}`;

  const customerName =
    payment.booking?.customer?.full_name ||
    payment.customer?.full_name ||
    (payment.booking?.customer?.first_name
      ? `${payment.booking.customer.first_name} ${payment.booking.customer.last_name || ""}`.trim()
      : null) ||
    "Customer";

  const customerEmail =
    payment.booking?.customer?.email || payment.customer?.email || "";

  const customerPhone =
    payment.booking?.customer?.phone || payment.customer?.phone || "";

  const booking = payment.booking || {};

  const formatCurrency = (amount) => {
    const num = Number(amount) || 0;
    return `$${num.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return "N/A";
    return new Date(dateStr).toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  };

  const handlePrint = () => {
    const content = printRef.current;
    const win = window.open("", "_blank");
    win.document.write(`
      <html>
      <head>
        <title>${title} - ${docNumber}</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 40px; color: #333; max-width: 800px; margin: 0 auto; }
          .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 3px solid #1e40af; }
          .logo { font-size: 24px; font-weight: bold; color: #1e40af; }
          .logo-sub { font-size: 12px; color: #666; }
          .doc-title { font-size: 28px; color: #1e40af; text-align: right; }
          .doc-number { font-size: 14px; color: #666; text-align: right; }
          .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
          .info-section h4 { color: #1e40af; margin-bottom: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
          .info-section p { margin: 4px 0; font-size: 14px; }
          table { width: 100%; border-collapse: collapse; margin: 20px 0; }
          th { background: #1e40af; color: white; padding: 12px; text-align: left; font-size: 13px; }
          td { padding: 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
          tr:nth-child(even) { background: #f8fafc; }
          .totals { margin-top: 20px; text-align: right; }
          .totals .row { display: flex; justify-content: flex-end; gap: 40px; padding: 6px 0; font-size: 14px; }
          .totals .total-row { font-size: 18px; font-weight: bold; color: #1e40af; border-top: 2px solid #1e40af; padding-top: 10px; margin-top: 10px; }
          .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
          .status-completed { background: #dcfce7; color: #166534; }
          .status-pending { background: #fef3c7; color: #92400e; }
          .status-failed { background: #fee2e2; color: #991b1b; }
          .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #666; font-size: 12px; }
          .notes { background: #f8fafc; padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 13px; }
          @media print { body { padding: 20px; } }
        </style>
      </head>
      <body>${content.innerHTML}</body>
      </html>
    `);
    win.document.close();
    setTimeout(() => {
      win.print();
    }, 300);
  };

  const statusClass =
    payment.status === "completed"
      ? "status-completed"
      : payment.status === "pending"
        ? "status-pending"
        : "status-failed";

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        {/* Modal Header */}
        <div className="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between rounded-t-2xl z-10">
          <div className="flex items-center gap-2">
            <FaFileInvoiceDollar className="text-blue-600 text-xl" />
            <h2 className="text-lg font-bold text-gray-900">{title}</h2>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={handlePrint}
              className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
            >
              <FaPrint /> Print / Download PDF
            </button>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600 p-2"
            >
              <FaTimes />
            </button>
          </div>
        </div>

        {/* Printable Content */}
        <div ref={printRef} className="p-8">
          {/* Header */}
          <div
            className="header"
            style={{
              display: "flex",
              justifyContent: "space-between",
              alignItems: "flex-start",
              marginBottom: 40,
              paddingBottom: 20,
              borderBottom: "3px solid #1e40af",
            }}
          >
            <div>
              <div
                className="logo"
                style={{ fontSize: 24, fontWeight: "bold", color: "#1e40af" }}
              >
                🚢 ShipWithGlowie
              </div>
              <div style={{ fontSize: 12, color: "#666" }}>
                AI-Powered Vehicle Shipping & Logistics
              </div>
              <div style={{ fontSize: 12, color: "#666", marginTop: 4 }}>
                support@shipwithglowie.com
              </div>
            </div>
            <div style={{ textAlign: "right" }}>
              <div
                style={{ fontSize: 28, color: "#1e40af", fontWeight: "bold" }}
              >
                {title.toUpperCase()}
              </div>
              <div style={{ fontSize: 14, color: "#666" }}>{docNumber}</div>
              <div style={{ fontSize: 13, color: "#666", marginTop: 4 }}>
                Date: {formatDate(payment.payment_date || payment.created_at)}
              </div>
            </div>
          </div>

          {/* Info Grid */}
          <div
            style={{
              display: "grid",
              gridTemplateColumns: "1fr 1fr",
              gap: 30,
              marginBottom: 30,
            }}
          >
            <div>
              <h4
                style={{
                  color: "#1e40af",
                  marginBottom: 8,
                  fontSize: 12,
                  textTransform: "uppercase",
                  letterSpacing: 1,
                }}
              >
                Bill To
              </h4>
              <p style={{ margin: "4px 0", fontSize: 14, fontWeight: "bold" }}>
                {customerName}
              </p>
              {customerEmail && (
                <p style={{ margin: "4px 0", fontSize: 14, color: "#666" }}>
                  {customerEmail}
                </p>
              )}
              {customerPhone && (
                <p style={{ margin: "4px 0", fontSize: 14, color: "#666" }}>
                  {customerPhone}
                </p>
              )}
            </div>
            <div>
              <h4
                style={{
                  color: "#1e40af",
                  marginBottom: 8,
                  fontSize: 12,
                  textTransform: "uppercase",
                  letterSpacing: 1,
                }}
              >
                Payment Details
              </h4>
              <p style={{ margin: "4px 0", fontSize: 14 }}>
                <strong>Reference:</strong> {payment.payment_reference || "N/A"}
              </p>
              <p style={{ margin: "4px 0", fontSize: 14 }}>
                <strong>Method:</strong>{" "}
                {(payment.payment_method || "N/A")
                  .replace(/_/g, " ")
                  .replace(/\b\w/g, (l) => l.toUpperCase())}
              </p>
              <p style={{ margin: "4px 0", fontSize: 14 }}>
                <strong>Status:</strong>{" "}
                <span
                  className={`status-badge ${statusClass}`}
                  style={{
                    display: "inline-block",
                    padding: "2px 10px",
                    borderRadius: 20,
                    fontSize: 12,
                    fontWeight: "bold",
                    background:
                      payment.status === "completed"
                        ? "#dcfce7"
                        : payment.status === "pending"
                          ? "#fef3c7"
                          : "#fee2e2",
                    color:
                      payment.status === "completed"
                        ? "#166534"
                        : payment.status === "pending"
                          ? "#92400e"
                          : "#991b1b",
                  }}
                >
                  {(payment.status || "N/A").toUpperCase()}
                </span>
              </p>
              {payment.transaction_id && (
                <p style={{ margin: "4px 0", fontSize: 14 }}>
                  <strong>Transaction ID:</strong> {payment.transaction_id}
                </p>
              )}
            </div>
          </div>

          {/* Line Items Table */}
          <table
            style={{
              width: "100%",
              borderCollapse: "collapse",
              margin: "20px 0",
            }}
          >
            <thead>
              <tr>
                <th
                  style={{
                    background: "#1e40af",
                    color: "white",
                    padding: 12,
                    textAlign: "left",
                    fontSize: 13,
                  }}
                >
                  Description
                </th>
                <th
                  style={{
                    background: "#1e40af",
                    color: "white",
                    padding: 12,
                    textAlign: "left",
                    fontSize: 13,
                  }}
                >
                  Booking Ref
                </th>
                <th
                  style={{
                    background: "#1e40af",
                    color: "white",
                    padding: 12,
                    textAlign: "right",
                    fontSize: 13,
                  }}
                >
                  Amount
                </th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td
                  style={{
                    padding: 12,
                    borderBottom: "1px solid #e5e7eb",
                    fontSize: 14,
                  }}
                >
                  <div>
                    <strong>Vehicle Shipping Service</strong>
                    {booking.vehicle && (
                      <div
                        style={{ fontSize: 12, color: "#666", marginTop: 2 }}
                      >
                        {booking.vehicle.year} {booking.vehicle.make}{" "}
                        {booking.vehicle.model}
                      </div>
                    )}
                    {booking.route && (
                      <div style={{ fontSize: 12, color: "#666" }}>
                        Route: {booking.route.origin} →{" "}
                        {booking.route.destination}
                      </div>
                    )}
                  </div>
                </td>
                <td
                  style={{
                    padding: 12,
                    borderBottom: "1px solid #e5e7eb",
                    fontSize: 14,
                  }}
                >
                  {booking.booking_reference || "N/A"}
                </td>
                <td
                  style={{
                    padding: 12,
                    borderBottom: "1px solid #e5e7eb",
                    fontSize: 14,
                    textAlign: "right",
                  }}
                >
                  {formatCurrency(payment.amount)}
                </td>
              </tr>
            </tbody>
          </table>

          {/* Totals */}
          <div style={{ textAlign: "right", marginTop: 20 }}>
            {booking.total_amount &&
              Number(booking.total_amount) !== Number(payment.amount) && (
                <>
                  <div
                    style={{
                      display: "flex",
                      justifyContent: "flex-end",
                      gap: 40,
                      padding: "6px 0",
                      fontSize: 14,
                    }}
                  >
                    <span style={{ color: "#666" }}>Booking Total:</span>
                    <span>{formatCurrency(booking.total_amount)}</span>
                  </div>
                  <div
                    style={{
                      display: "flex",
                      justifyContent: "flex-end",
                      gap: 40,
                      padding: "6px 0",
                      fontSize: 14,
                    }}
                  >
                    <span style={{ color: "#666" }}>Previously Paid:</span>
                    <span>
                      {formatCurrency(
                        Number(booking.paid_amount || 0) -
                          Number(payment.amount || 0),
                      )}
                    </span>
                  </div>
                </>
              )}
            <div
              style={{
                display: "flex",
                justifyContent: "flex-end",
                gap: 40,
                fontSize: 18,
                fontWeight: "bold",
                color: "#1e40af",
                borderTop: "2px solid #1e40af",
                paddingTop: 10,
                marginTop: 10,
              }}
            >
              <span>{isReceipt ? "Amount Paid" : "Amount Due"}:</span>
              <span>{formatCurrency(payment.amount)}</span>
            </div>
            {booking.balance_amount > 0 && (
              <div
                style={{
                  display: "flex",
                  justifyContent: "flex-end",
                  gap: 40,
                  padding: "6px 0",
                  fontSize: 14,
                  color: "#dc2626",
                }}
              >
                <span>Outstanding Balance:</span>
                <span>{formatCurrency(booking.balance_amount)}</span>
              </div>
            )}
          </div>

          {/* Notes */}
          {payment.notes && (
            <div
              style={{
                background: "#f8fafc",
                padding: 15,
                borderRadius: 8,
                marginTop: 20,
                fontSize: 13,
              }}
            >
              <strong>Notes:</strong> {payment.notes}
            </div>
          )}

          {/* Footer */}
          <div
            style={{
              marginTop: 50,
              paddingTop: 20,
              borderTop: "1px solid #e5e7eb",
              textAlign: "center",
              color: "#666",
              fontSize: 12,
            }}
          >
            <p>Thank you for choosing ShipWithGlowie Auto</p>
            <p>
              This is a computer-generated document. No signature is required.
            </p>
            <p style={{ marginTop: 8, color: "#999" }}>
              ShipWithGlowie Auto — AI-Powered Vehicle Shipping & Logistics |
              support@shipwithglowie.com
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default InvoiceModal;
