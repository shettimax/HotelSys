// /js/print.js - Print functionality for invoices and keycards

// Use IIFE to avoid variable conflicts
(function() {
    let isPrinting = false; // Flag to prevent multiple prints

    /**
     * Print Receipt function
     */
    function printReceipt() {
        // Prevent multiple print windows
        if (isPrinting) {
            console.log('Print already in progress...');
            return;
        }
        
        isPrinting = true;
        
        try {
            // Use data from PHP
            const printData = window.invoicePrintData;
            
            if (!printData) {
                alert('Invoice data not available. Please refresh the page.');
                isPrinting = false;
                return;
            }
            
            // Open print window
            const printWindow = window.open('', '_blank', 'width=900,height=700');
            
            if (!printWindow) {
                alert('Please allow popups to print receipt.');
                isPrinting = false;
                return;
            }
            
            // Create receipt HTML with integrated CSS
            const receiptHtml = createReceiptHtml(printData);
            
            printWindow.document.write(receiptHtml);
            printWindow.document.close();
            
            // Wait for content to load before printing
            printWindow.onload = function() {
                setTimeout(function() {
                    try {
                        printWindow.focus();
                        printWindow.print();
                    } catch (e) {
                        console.error('Print error:', e);
                    }
                    
                    // Reset flag after delay
                    setTimeout(function() {
                        isPrinting = false;
                        try {
                            printWindow.close();
                        } catch (e) {
                            console.log('Print window could not be closed automatically');
                        }
                    }, 1000);
                }, 500);
            };
            
            // Fallback in case onload doesn't fire
            setTimeout(function() {
                if (isPrinting) {
                    isPrinting = false;
                }
            }, 5000);
            
        } catch (error) {
            console.error('Print receipt error:', error);
            isPrinting = false;
            // Fallback to default print
            window.print();
        }
    }

    /**
     * Print Keycard function
     */
    function printKeycard() {
        // Prevent multiple print windows
        if (isPrinting) {
            console.log('Print already in progress...');
            return;
        }
        
        isPrinting = true;
        
        try {
            // Use keycard data from PHP
            const printData = window.keycardData;
            
            if (!printData) {
                alert('Keycard data not available. Please refresh the page.');
                isPrinting = false;
                return;
            }
            
            // Open print window
            const printWindow = window.open('', '_blank', 'width=900,height=600');
            
            if (!printWindow) {
                alert('Please allow popups to print keycard.');
                isPrinting = false;
                return;
            }
            
            // Create keycard HTML with integrated CSS
            const keycardHtml = createKeycardHtml(printData);
            
            printWindow.document.write(keycardHtml);
            printWindow.document.close();
            
            // Wait for content to load before printing
            printWindow.onload = function() {
                setTimeout(function() {
                    try {
                        printWindow.focus();
                        printWindow.print();
                    } catch (e) {
                        console.error('Print error:', e);
                    }
                    
                    // Reset flag after delay
                    setTimeout(function() {
                        isPrinting = false;
                        try {
                            printWindow.close();
                        } catch (e) {
                            console.log('Print window could not be closed automatically');
                        }
                    }, 1000);
                }, 500);
            };
            
            // Fallback in case onload doesn't fire
            setTimeout(function() {
                if (isPrinting) {
                    isPrinting = false;
                }
            }, 5000);
            
        } catch (error) {
            console.error('Print keycard error:', error);
            isPrinting = false;
            // Fallback to default print
            window.print();
        }
    }

    /**
     * Create HTML for Receipt with custom CSS matching hotel theme
     */
    function createReceiptHtml(data) {
        const currentDate = new Date().toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        return `
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Receipt - ${data.invoiceNumber || 'Unknown'} - ${data.hotelName || 'Hotel'}</title>
            <style>
                /* Receipt Print Styles - Based on your hotel theme */
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 12px;
                    line-height: 1.5;
                    color: #00ff99;
                    background: #0a0a0a;
                    padding: 20px;
                    margin: 0;
                }
                
                .receipt-container {
                    max-width: 800px;
                    margin: 0 auto;
                    border: 3px solid #00ff99;
                    border-radius: 8px;
                    padding: 25px;
                    background: #1a1a1a;
                    box-shadow: 0 0 20px rgba(0, 255, 153, 0.2);
                }
                
                /* Header */
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #00ff99;
                }
                
                .hotel-name {
                    font-size: 28px;
                    font-weight: bold;
                    margin-bottom: 8px;
                    color: #00ff99;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                
                .hotel-tagline {
                    font-size: 14px;
                    font-style: italic;
                    margin-bottom: 10px;
                    color: #00cc99;
                }
                
                .hotel-address {
                    font-size: 12px;
                    color: #00cc99;
                }
                
                /* Invoice Info */
                .invoice-info {
                    margin-bottom: 25px;
                    padding: 15px;
                    background: #000;
                    border-radius: 6px;
                    border: 1px solid #00ff99;
                }
                
                .invoice-header {
                    font-size: 18px;
                    font-weight: bold;
                    margin-bottom: 15px;
                    color: #00ff99;
                    text-align: center;
                }
                
                .invoice-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                    padding-bottom: 8px;
                    border-bottom: 1px dashed #00ff99;
                }
                
                .invoice-label {
                    font-weight: bold;
                    color: #00ff99;
                }
                
                .invoice-value {
                    color: #00ff99;
                    font-weight: bold;
                }
                
                /* Status Badge */
                .status-badge {
                    display: inline-block;
                    padding: 4px 12px;
                    border-radius: 15px;
                    font-weight: bold;
                    font-size: 12px;
                    margin-left: 10px;
                    text-transform: uppercase;
                }
                
                .status-paid {
                    background: #00ff99;
                    color: #000;
                }
                
                .status-unpaid {
                    background: #ff5555;
                    color: #fff;
                }
                
                /* Guest Info */
                .guest-info {
                    margin-bottom: 20px;
                    padding: 15px;
                    background: #000;
                    border-radius: 6px;
                    border: 1px solid #00ff99;
                }
                
                .guest-header {
                    font-size: 16px;
                    font-weight: bold;
                    margin-bottom: 12px;
                    color: #00ff99;
                    text-transform: uppercase;
                }
                
                .guest-row {
                    display: flex;
                    margin-bottom: 8px;
                }
                
                .guest-label {
                    font-weight: bold;
                    min-width: 100px;
                    color: #00ff99;
                }
                
                .guest-value {
                    flex: 1;
                    color: #00ff99;
                }
                
                /* Booking Info */
                .booking-info {
                    margin-bottom: 20px;
                    padding: 15px;
                    background: #000;
                    border-radius: 6px;
                    border: 1px solid #00ff99;
                }
                
                .booking-header {
                    font-size: 16px;
                    font-weight: bold;
                    margin-bottom: 12px;
                    color: #00ff99;
                    text-transform: uppercase;
                }
                
                .booking-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 12px;
                }
                
                .booking-item {
                    padding: 10px;
                    background: #111;
                    border-radius: 4px;
                    border: 1px solid #00ff99;
                }
                
                .booking-label {
                    font-size: 11px;
                    color: #00cc99;
                    margin-bottom: 5px;
                    text-transform: uppercase;
                }
                
                .booking-value {
                    font-size: 14px;
                    font-weight: bold;
                    color: #00ff99;
                }
                
                /* Payment Info */
                .payment-info {
                    margin-bottom: 25px;
                    padding: 15px;
                    background: #000;
                    border-radius: 6px;
                    border: 1px solid #00ff99;
                }
                
                .payment-header {
                    font-size: 16px;
                    font-weight: bold;
                    margin-bottom: 12px;
                    color: #00ff99;
                    text-transform: uppercase;
                }
                
                .payment-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                    padding-bottom: 8px;
                    border-bottom: 1px dashed #00ff99;
                }
                
                .payment-label {
                    font-weight: bold;
                    color: #00ff99;
                }
                
                .payment-value {
                    color: #00ff99;
                    font-weight: bold;
                }
                
                /* Amount Highlights */
                .total-amount {
                    color: #00ff99;
                    font-size: 18px;
                }
                
                .balance-due {
                    color: #ff5555;
                }
                
                /* Footer */
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #00ff99;
                }
                
                .thank-you {
                    font-size: 16px;
                    font-weight: bold;
                    margin-bottom: 10px;
                    color: #00ff99;
                }
                
                .print-date {
                    font-size: 11px;
                    color: #00cc99;
                    margin-bottom: 10px;
                }
                
                .contact-info {
                    font-size: 12px;
                    color: #00cc99;
                    font-style: italic;
                }
                
                /* Print styles */
                @media print {
                    body {
                        padding: 0 !important;
                        margin: 0 !important;
                        background: #0a0a0a !important;
                        color: #00ff99 !important;
                    }
                    
                    .receipt-container {
                        max-width: 100% !important;
                        margin: 0 !important;
                        padding: 15px !important;
                        border: 3px solid #000 !important;
                        border-color: #00ff99 !important;
                        box-shadow: none !important;
                    }
                    
                    @page {
                        margin: 0.5in;
                    }
                }
                
                /* Hide from screen */
                @media screen {
                    body {
                        background: #0a0a0a;
                        color: #00ff99;
                    }
                }
            </style>
        </head>
        <body>
            <div class="receipt-container">
                <!-- Header -->
                <div class="header">
                    <div class="hotel-name">${data.hotelName || 'Hotel Management System'}</div>
                    <div class="hotel-tagline">${data.hotelTagline || ''}</div>
                    <div class="hotel-address">${data.hotelAddress || ''}</div>
                </div>
                
                <!-- Invoice Information -->
                <div class="invoice-info">
                    <div class="invoice-header">
                        INVOICE RECEIPT
                        <span class="status-badge ${data.invoicePaid ? 'status-paid' : 'status-unpaid'}">
                            ${data.paymentStatus || 'PENDING'}
                        </span>
                    </div>
                    <div class="invoice-row">
                        <div class="invoice-label">Invoice Number:</div>
                        <div class="invoice-value">${data.invoiceNumber || 'Unknown'}</div>
                    </div>
                    <div class="invoice-row">
                        <div class="invoice-label">Payment Date:</div>
                        <div class="invoice-value">${data.paidDate || 'Not Paid'}</div>
                    </div>
                </div>
                
                <!-- Guest Information -->
                <div class="guest-info">
                    <div class="guest-header">GUEST INFORMATION</div>
                    <div class="guest-row">
                        <div class="guest-label">Name:</div>
                        <div class="guest-value">${data.guestName || 'N/A'}</div>
                    </div>
                    <div class="guest-row">
                        <div class="guest-label">Email:</div>
                        <div class="guest-value">${data.guestEmail || 'N/A'}</div>
                    </div>
                    <div class="guest-row">
                        <div class="guest-label">Phone:</div>
                        <div class="guest-value">${data.guestPhone || 'N/A'}</div>
                    </div>
                </div>
                
                <!-- Booking Information -->
                <div class="booking-info">
                    <div class="booking-header">BOOKING DETAILS</div>
                    <div class="booking-grid">
                        <div class="booking-item">
                            <div class="booking-label">Room Number</div>
                            <div class="booking-value">${data.roomNumber || 'N/A'}</div>
                        </div>
                        <div class="booking-item">
                            <div class="booking-label">Building</div>
                            <div class="booking-value">${data.buildingName || 'N/A'} (${data.buildingType || 'Standard'})</div>
                        </div>
                        <div class="booking-item">
                            <div class="booking-label">Check-in Date</div>
                            <div class="booking-value">${data.checkinDate || 'N/A'}</div>
                        </div>
                        <div class="booking-item">
                            <div class="booking-label">Check-out Date</div>
                            <div class="booking-value">${data.checkoutDate || 'N/A'}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="payment-info">
                    <div class="payment-header">PAYMENT SUMMARY</div>
                    <div class="payment-row">
                        <div class="payment-label">Total Amount:</div>
                        <div class="payment-value total-amount">${data.totalAmount || '₦0.00'}</div>
                    </div>
                    <div class="payment-row">
                        <div class="payment-label">Amount Paid:</div>
                        <div class="payment-value">${data.amountPaid || '₦0.00'}</div>
                    </div>
                    <!-- Only show Balance Due if it's NOT ₦0.00 -->
                    ${data.balanceDue !== '₦0.00' ? `
                    <div class="payment-row">
                        <div class="payment-label">Balance Due:</div>
                        <div class="payment-value balance-due">${data.balanceDue || '₦0.00'}</div>
                    </div>
                    ` : ''}
                    <div class="payment-row">
                        <div class="payment-label">Duration:</div>
                        <div class="payment-value">${data.nights || '0'} night(s)</div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="footer">
                    <div class="thank-you">Thank you for your stay at our hotel!</div>
                    <div class="print-date">Printed on: ${currentDate}</div>
                    <div class="contact-info">For inquiries, please contact the front desk.</div>
                </div>
            </div>
            
            <script>
                // Auto-print when loaded
                window.addEventListener('load', function() {
                    setTimeout(function() {
                        try {
                            window.print();
                            setTimeout(function() {
                                try {
                                    window.close();
                                } catch (e) {
                                    // Window might be blocked
                                }
                            }, 100);
                        } catch (e) {
                            console.error('Print failed:', e);
                        }
                    }, 500);
                });
            </script>
        </body>
        </html>
        `;
    }

    /**
     * Create HTML for Keycard with custom CSS matching hotel theme
     */
    function createKeycardHtml(data) {
        return `
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Keycard - ${data.guestName || 'Guest'} - ${data.hotelName || 'Hotel'}</title>
            <style>
                /* Keycard Print Styles - Based on your hotel theme */
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #00ff99;
                    background: #0a0a0a;
                    padding: 15px;
                    margin: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                }
                
                .keycard-container {
                    width: 100%;
                    max-width: 500px;
                    border: 4px solid #00ff99;
                    border-radius: 12px;
                    padding: 20px;
                    background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
                    box-shadow: 0 0 30px rgba(0, 255, 153, 0.3);
                    position: relative;
                    overflow: hidden;
                }
                
                /* Keycard Header */
                .keycard-header {
                    text-align: center;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 2px solid #00ff99;
                }
                
                .keycard-title {
                    font-size: 22px;
                    font-weight: bold;
                    margin-bottom: 8px;
                    color: #00ff99;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                
                .keycard-notice {
                    font-size: 12px;
                    color: #ff5555;
                    font-weight: bold;
                    margin-bottom: 10px;
                    padding: 6px;
                    background: rgba(255, 85, 85, 0.1);
                    border: 2px solid #ff5555;
                    border-radius: 4px;
                    text-transform: uppercase;
                }
                
                /* Hotel Info */
                .hotel-name {
                    font-size: 24px;
                    font-weight: bold;
                    margin-bottom: 5px;
                    color: #00ff99;
                }
                
                .hotel-tagline {
                    font-size: 12px;
                    font-style: italic;
                    margin-bottom: 6px;
                    color: #00cc99;
                }
                
                /* Guest Info */
                .guest-section {
                    margin: 20px 0;
                    padding: 15px;
                    background: #000;
                    border-radius: 8px;
                    border: 2px solid #00ff99;
                }
                
                .guest-header {
                    font-size: 14px;
                    font-weight: bold;
                    margin-bottom: 12px;
                    color: #00ff99;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    text-transform: uppercase;
                }
                
                .guest-id {
                    background: #00ff99;
                    color: #000;
                    padding: 3px 10px;
                    border-radius: 12px;
                    font-size: 11px;
                    font-weight: bold;
                }
                
                .guest-name {
                    font-size: 18px;
                    font-weight: bold;
                    text-align: center;
                    margin: 12px 0;
                    padding: 10px;
                    background: #111;
                    border-radius: 6px;
                    border: 2px dashed #00ff99;
                    text-transform: uppercase;
                }
                
                /* Room Info */
                .room-section {
                    margin: 20px 0;
                    padding: 15px;
                    background: #000;
                    border-radius: 8px;
                    border: 2px solid #00ff99;
                }
                
                .room-header {
                    font-size: 14px;
                    font-weight: bold;
                    margin-bottom: 12px;
                    color: #00ff99;
                    text-align: center;
                    text-transform: uppercase;
                }
                
                .room-details {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 10px;
                    margin-top: 12px;
                }
                
                .room-item {
                    text-align: center;
                    padding: 10px;
                    background: #111;
                    border-radius: 6px;
                    border: 1px solid #00ff99;
                }
                
                .room-label {
                    font-size: 10px;
                    color: #00cc99;
                    margin-bottom: 5px;
                    text-transform: uppercase;
                }
                
                .room-value {
                    font-size: 14px;
                    font-weight: bold;
                    color: #00ff99;
                }
                
                /* Room Number - Large Display */
                .room-number-display {
                    text-align: center;
                    margin: 15px 0;
                    padding: 15px;
                    background: linear-gradient(135deg, #00ff99 0%, #00cc66 100%);
                    border-radius: 10px;
                    color: #000;
                }
                
                .room-number-label {
                    font-size: 12px;
                    margin-bottom: 8px;
                    opacity: 0.9;
                    letter-spacing: 1px;
                    font-weight: bold;
                    text-transform: uppercase;
                }
                
                .room-number-value {
                    font-size: 36px;
                    font-weight: bold;
                    letter-spacing: 2px;
                    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
                }
                
                /* Instructions */
                .instructions {
                    margin-top: 20px;
                    padding: 12px;
                    background: rgba(0, 255, 153, 0.1);
                    border-radius: 8px;
                    border: 2px dashed #00ff99;
                }
                
                .instructions-title {
                    font-size: 12px;
                    font-weight: bold;
                    margin-bottom: 8px;
                    color: #00ff99;
                    text-align: center;
                    text-transform: uppercase;
                }
                
                .instructions-text {
                    font-size: 10px;
                    color: #00cc99;
                    line-height: 1.4;
                    text-align: center;
                }
                
                /* Footer */
                .keycard-footer {
                    text-align: center;
                    margin-top: 15px;
                    padding-top: 12px;
                    border-top: 1px solid #00ff99;
                }
                
                .thank-you {
                    font-size: 14px;
                    font-weight: bold;
                    margin-bottom: 8px;
                    color: #00ff99;
                }
                
                .contact-info {
                    font-size: 10px;
                    color: #00cc99;
                    font-style: italic;
                }
                
                /* Decorative elements */
                .keycard-container::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: linear-gradient(90deg, #00ff99, #00cc66, #00ff99);
                }
                
                .keycard-container::after {
                    content: '';
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: linear-gradient(90deg, #00ff99, #00cc66, #00ff99);
                }
                
                /* Print styles */
                @media print {
                    body {
                        padding: 0 !important;
                        margin: 0 !important;
                        background: #0a0a0a !important;
                        color: #00ff99 !important;
                        display: block !important;
                        min-height: auto !important;
                    }
                    
                    .keycard-container {
                        max-width: 100% !important;
                        margin: 0 !important;
                        padding: 15px !important;
                        border: 4px solid #00ff99 !important;
                        box-shadow: none !important;
                        page-break-inside: avoid;
                    }
                    
                    @page {
                        margin: 0.25in;
                        size: auto;
                    }
                }
                
                /* Hide from screen */
                @media screen {
                    body {
                        background: #0a0a0a;
                        color: #00ff99;
                    }
                }
            </style>
        </head>
        <body>
            <div class="keycard-container">
                <!-- Keycard Header -->
                <div class="keycard-header">
                    <div class="keycard-title">${data.hotelName || 'Hotel'} KEYCARD</div>
                    <div class="hotel-tagline">${data.hotelTagline || ''}</div>
                    <div class="keycard-notice">PRESENT AT FRONT DESK FOR ROOM KEY</div>
                </div>
                
                <!-- Guest Information -->
                <div class="guest-section">
                    <div class="guest-header">
                        <span>GUEST</span>
                        <span class="guest-id">#${data.invoiceNumber || 'N/A'}</span>
                    </div>
                    <div class="guest-name">${data.guestName || 'Guest Name'}</div>
                </div>
                
                <!-- Room Number - Large Display -->
                <div class="room-number-display">
                    <div class="room-number-label">ASSIGNED ROOM</div>
                    <div class="room-number-value">${data.roomNumber || 'N/A'}</div>
                </div>
                
                <!-- Room Details -->
                <div class="room-section">
                    <div class="room-header">ROOM DETAILS</div>
                    <div class="room-details">
                        <div class="room-item">
                            <div class="room-label">Building</div>
                            <div class="room-value">${data.buildingName || 'N/A'}</div>
                        </div>
                        <div class="room-item">
                            <div class="room-label">Type</div>
                            <div class="room-value">${data.buildingType || 'Standard'}</div>
                        </div>
                        <div class="room-item">
                            <div class="room-label">Check-in</div>
                            <div class="room-value">${data.checkinDate || 'N/A'}</div>
                        </div>
                        <div class="room-item">
                            <div class="room-label">Check-out</div>
                            <div class="room-value">${data.checkoutDate || 'N/A'}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="instructions">
                    <div class="instructions-title">KEY COLLECTION INSTRUCTIONS</div>
                    <div class="instructions-text">
                        1. Present this card at front desk<br>
                        2. Provide valid photo ID<br>
                        3. Collect room key<br>
                        4. Return key at checkout (by ${data.checkoutDate || 'Check-out date'})
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="keycard-footer">
                    <div class="thank-you">Enjoy Your Stay!</div>
                    <div class="contact-info">Front Desk • 24/7 Assistance</div>
                </div>
            </div>
            
            <script>
                // Auto-print when loaded
                window.addEventListener('load', function() {
                    setTimeout(function() {
                        try {
                            window.print();
                            setTimeout(function() {
                                try {
                                    window.close();
                                } catch (e) {
                                    // Window might be blocked
                                }
                            }, 100);
                        } catch (e) {
                            console.error('Print failed:', e);
                        }
                    }, 500);
                });
            </script>
        </body>
        </html>
        `;
    }

    /**
     * Initialize print buttons
     */
    function initPrintButtons() {
        // Add click handlers to receipt buttons
        document.querySelectorAll('.btn-print-receipt').forEach(button => {
            if (!button.dataset.printListenerAdded) {
                button.dataset.printListenerAdded = 'true';
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    printReceipt();
                    
                    return false;
                }, true);
            }
        });
        
        // Add click handlers to keycard buttons
        document.querySelectorAll('.btn-print-keycard').forEach(button => {
            if (!button.dataset.printListenerAdded) {
                button.dataset.printListenerAdded = 'true';
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    printKeycard();
                    
                    return false;
                }, true);
            }
        });
    }

    // Initialize when DOM is loaded
    let hasInitialized = false;
    document.addEventListener('DOMContentLoaded', function() {
        if (!hasInitialized) {
            hasInitialized = true;
            setTimeout(initPrintButtons, 100);
        }
    });

    // Export functions for use in other files
    window.printReceipt = printReceipt;
    window.printKeycard = printKeycard;
    window.initPrintButtons = initPrintButtons;

})(); // End IIFE
