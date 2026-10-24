// /js/bookings.js - Booking-related JavaScript functions

// Store customer data
var customerData = [];
var bookingMode = false;
var pendingBooking = null;

// Initialize function to set data from PHP
function initBookingData(customers, isBookingMode, bookingData) {
    customerData = customers;
    bookingMode = isBookingMode;
    pendingBooking = bookingData;
}

// Function to open view modal
function viewCustomer(customerId) {
    var customer = customerData.find(function(c) { return c.id == customerId; });
    if (!customer) {
        console.error('Customer not found:', customerId);
        alert('Customer not found in data. Please refresh the page.');
        return;
    }
    
    // Format the content
    var content = '<div class="row">' +
        '<div class="col-md-4 text-center">' +
            '<div style="font-size: 60px; margin-bottom: 15px;" aria-hidden="true">' +
                getGenderAvatar(customer.gender) +
            '</div>' +
            '<h4>' + escapeHtml(customer.firstname) + ' ' + escapeHtml(customer.lastname) + '</h4>' +
            '<p class="text-muted">Customer ID: #' + customer.id + '</p>' +
            '<p class="text-muted">Member since: ' + formatDate(customer.created_at) + '</p>' +
        '</div>' +
        '<div class="col-md-8">' +
            '<h5>Contact Information</h5>' +
            '<table class="table table-condensed">' +
                '<tr><td width="120"><strong>Email:</strong></td><td>' + (customer.email ? escapeHtml(customer.email) : '<span class="text-muted">Not provided</span>') + '</td></tr>' +
                '<tr><td><strong>Phone:</strong></td><td>' + (customer.phone ? escapeHtml(customer.phone) : '<span class="text-muted">Not provided</span>') + '</td></tr>' +
                '<tr><td><strong>Address:</strong></td><td>' + (customer.address ? escapeHtml(customer.address) : '<span class="text-muted">Not provided</span>') + '</td></tr>' +
                '<tr><td><strong>Gender:</strong></td><td>' + capitalizeFirst(customer.gender) + '</td></tr>' +
            '</table>' +
            
            '<h5>Identification</h5>' +
            '<table class="table table-condensed">' +
                '<tr><td width="120"><strong>ID Type:</strong></td><td>' + (customer.id_type ? escapeHtml(capitalizeFirst(customer.id_type.replace(/_/g, ' '))) : '<span class="text-muted">Not provided</span>') + '</td></tr>' +
                '<tr><td><strong>ID Number:</strong></td><td>' + (customer.id_number ? escapeHtml(customer.id_number) : '<span class="text-muted">Not provided</span>') + '</td></tr>' +
                '<tr><td><strong>Has Vehicle:</strong></td><td>' + (customer.has_car ? '<span class="text-success">Yes</span>' : '<span class="text-muted">No</span>') + '</td></tr>' +
            '</table>' +
            
            '<h5>Booking History</h5>' +
            '<table class="table table-condensed">' +
                '<tr><td width="120"><strong>Total Bookings:</strong></td><td>' + (customer.total_bookings || 0) + '</td></tr>' +
                '<tr><td><strong>Total Spent:</strong></td><td><strong>₦' + formatCurrency(customer.total_spent || 0) + '</strong></td></tr>' +
            '</table>';
            
            if (customer.notes) {
                content += '<h5>Notes</h5><div class="well well-sm">' + escapeHtml(customer.notes).replace(/\n/g, '<br>') + '</div>';
            }
            
        content += '</div></div>';
    
    // Update modal content
    document.getElementById('viewCustomerModalLabel').textContent = 'Customer Profile: ' + escapeHtml(customer.firstname) + ' ' + escapeHtml(customer.lastname);
    document.getElementById('viewCustomerModalBody').innerHTML = content;
    
    // Set up edit button
    document.getElementById('viewModalEditBtn').onclick = function() {
        $('#viewCustomerModal').modal('hide');
        setTimeout(function() {
            editCustomer(customerId);
        }, 300);
    };
    
    // Set up select button for booking mode
    if (bookingMode) {
        var selectBtn = document.getElementById('viewModalSelectBtn');
        if (selectBtn) {
            selectBtn.style.display = 'inline-block';
            selectBtn.onclick = function() {
                if (confirm('Select ' + escapeHtml(customer.firstname) + ' ' + escapeHtml(customer.lastname) + ' for this booking?')) {
                    window.location.href = 'admin_customers.php?booking_mode=1&select_customer=' + customerId;
                }
            };
        }
    }
    
    // Show modal
    $('#viewCustomerModal').modal('show');
}

// Function to select customer for booking (for duplicate alerts)
function selectCustomerForBooking(customerId) {
    if (confirm('Select this customer for your booking?')) {
        window.location.href = 'admin_customers.php?booking_mode=1&select_customer=' + customerId;
    }
}

// Function to open edit modal
function editCustomer(customerId) {
    console.log('Looking for customer ID:', customerId);
    console.log('Customer data:', customerData);
    
    var customer = customerData.find(function(c) { return c.id == customerId; });
    if (!customer) {
        console.error('Customer not found in data:', customerId);
        alert('Customer data not found. Please refresh the page and try again.');
        return;
    }
    
    console.log('Found customer:', customer);
    
    // Create edit form content
    var formContent = '<input type="hidden" name="update_customer" value="1">' +
        '<input type="hidden" name="customer_id" value="' + customer.id + '">' +
        
        '<div class="row">' +
            '<div class="col-md-6">' +
                '<div class="form-group">' +
                    '<label for="edit_firstname_' + customer.id + '">First Name *</label>' +
                    '<input type="text" name="firstname" id="edit_firstname_' + customer.id + '" class="form-control" value="' + escapeHtml(customer.firstname || '') + '" required autocomplete="given-name">' +
                '</div>' +
            '</div>' +
            '<div class="col-md-6">' +
                '<div class="form-group">' +
                    '<label for="edit_lastname_' + customer.id + '">Last Name *</label>' +
                    '<input type="text" name="lastname" id="edit_lastname_' + customer.id + '" class="form-control" value="' + escapeHtml(customer.lastname || '') + '" required autocomplete="family-name">' +
                '</div>' +
            '</div>' +
        '</div>' +
        
        '<div class="row">' +
            '<div class="col-md-6">' +
                '<div class="form-group">' +
                    '<label for="edit_gender_' + customer.id + '">Gender *</label>' +
                    '<select name="gender" id="edit_gender_' + customer.id + '" class="form-control" required autocomplete="sex">' +
                        '<option value="male"' + ((customer.gender || '') == 'male' ? ' selected' : '') + '>Male</option>' +
                        '<option value="female"' + ((customer.gender || '') == 'female' ? ' selected' : '') + '>Female</option>' +
                        '<option value="other"' + ((customer.gender || '') == 'other' ? ' selected' : '') + '>Other</option>' +
                    '</select>' +
                '</div>' +
            '</div>' +
            '<div class="col-md-6">' +
                '<div class="form-group">' +
                    '<label for="edit_email_' + customer.id + '">Email *</label>' +
                    '<input type="email" name="email" id="edit_email_' + customer.id + '" class="form-control" value="' + escapeHtml(customer.email || '') + '" required autocomplete="email">' +
                '</div>' +
            '</div>' +
        '</div>' +
        
        '<div class="row">' +
            '<div class="col-md-6">' +
                '<div class="form-group">' +
                    '<label for="edit_phone_' + customer.id + '">Phone</label>' +
                    '<input type="text" name="phone" id="edit_phone_' + customer.id + '" class="form-control" value="' + escapeHtml(customer.phone || '') + '" autocomplete="tel">' +
                '</div>' +
            '</div>' +
            '<div class="col-md-6">' +
                '<div class="form-group">' +
                    '<label class="checkbox-inline">' +
                        '<input type="checkbox" name="has_car" id="edit_has_car_' + customer.id + '" value="1"' + ((customer.has_car == 1 || customer.has_car == '1') ? ' checked' : '') + '> Has vehicle' +
                    '</label>' +
                '</div>' +
            '</div>' +
        '</div>' +
        
        '<div class="form-group">' +
            '<label for="edit_address_' + customer.id + '">Address</label>' +
            '<textarea name="address" id="edit_address_' + customer.id + '" class="form-control" rows="2" autocomplete="street-address">' + escapeHtml(customer.address || '') + '</textarea>' +
        '</div>' +
        
        '<div class="row">' +
            '<div class="col-md-6">' +
                '<div class="form-group">' +
                    '<label for="edit_id_type_' + customer.id + '">ID Type</label>' +
                    '<select name="id_type" id="edit_id_type_' + customer.id + '" class="form-control">' +
                        '<option value="">Select ID type</option>' +
                        '<option value="drivers_license"' + ((customer.id_type || '') == 'drivers_license' ? ' selected' : '') + '>Driver\'s License</option>' +
                        '<option value="nin"' + ((customer.id_type || '') == 'nin' ? ' selected' : '') + '>NIN</option>' +
                        '<option value="international_passport"' + ((customer.id_type || '') == 'international_passport' ? ' selected' : '') + '>Passport</option>' +
                        '<option value="school_id"' + ((customer.id_type || '') == 'school_id' ? ' selected' : '') + '>School ID</option>' +
                        '<option value="voters_card"' + ((customer.id_type || '') == 'voters_card' ? ' selected' : '') + '>Voter\'s Card</option>' +
                        '<option value="other"' + ((customer.id_type || '') == 'other' ? ' selected' : '') + '>Other</option>' +
                    '</select>' +
                '</div>' +
            '</div>' +
            '<div class="col-md-6">' +
                '<div class="form-group">' +
                    '<label for="edit_id_number_' + customer.id + '">ID Number</label>' +
                    '<input type="text" name="id_number" id="edit_id_number_' + customer.id + '" class="form-control" value="' + escapeHtml(customer.id_number || '') + '">' +
                '</div>' +
            '</div>' +
        '</div>' +
        
        '<div class="form-group">' +
            '<label for="edit_notes_' + customer.id + '">Notes</label>' +
            '<textarea name="notes" id="edit_notes_' + customer.id + '" class="form-control" rows="3">' + escapeHtml(customer.notes || '') + '</textarea>' +
        '</div>';
    
    // Update modal
    document.getElementById('editCustomerModalLabel').textContent = 'Edit Customer: ' + escapeHtml(customer.firstname) + ' ' + escapeHtml(customer.lastname);
    document.getElementById('editCustomerModalBody').innerHTML = formContent;
    
    // Show modal
    $('#editCustomerModal').modal('show');
}

// Function to open add customer modal
function openAddCustomerModal() {
    // Clear form
    document.getElementById('addCustomerForm').reset();
    $('#addCustomerModal').modal('show');
}

// Validate add customer form before submission
function validateAddCustomerForm() {
    var email = document.getElementById('new_email').value;
    var phone = document.getElementById('new_phone').value;
    var idType = document.getElementById('new_id_type').value;
    var idNumber = document.getElementById('new_id_number').value;
    
    // Quick client-side duplicate check (based on loaded data)
    var duplicate = false;
    var duplicateMessage = '';
    
    if (customerData) {
        // Check email duplicate
        var emailDuplicate = customerData.find(function(c) { return c.email.toLowerCase() === email.toLowerCase(); });
        if (emailDuplicate) {
            duplicate = true;
            duplicateMessage += 'Email "' + email + '" already exists for customer: ' + emailDuplicate.firstname + ' ' + emailDuplicate.lastname + '\n';
        }
        
        // Check phone duplicate (if phone provided)
        if (phone) {
            var phoneDuplicate = customerData.find(function(c) { return c.phone && c.phone === phone; });
            if (phoneDuplicate) {
                duplicate = true;
                duplicateMessage += 'Phone "' + phone + '" already exists for customer: ' + phoneDuplicate.firstname + ' ' + phoneDuplicate.lastname + '\n';
            }
        }
        
        // Check ID duplicate (if ID type and number provided)
        if (idType && idNumber) {
            var idDuplicate = customerData.find(function(c) { return c.id_type === idType && c.id_number === idNumber; });
            if (idDuplicate) {
                duplicate = true;
                duplicateMessage += idType + ' number "' + idNumber + '" already exists for customer: ' + idDuplicate.firstname + ' ' + idDuplicate.lastname + '\n';
            }
        }
    }
    
    if (duplicate) {
        alert('Potential duplicate found!\n\n' + duplicateMessage + '\nPlease check before proceeding.');
        return false; // Prevent form submission
    }
    
    return true; // Allow form submission
}

// Helper functions
function getGenderAvatar(gender) {
    switch(gender) {
        case 'male': return '♂';
        case 'female': return '♀';
        default: return '👤';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function capitalizeFirst(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function formatDate(dateString) {
    if (!dateString) return 'Unknown';
    var date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatCurrency(amount) {
    return parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Real-time search suggestion (optional)
    var searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // You could add AJAX real-time search here if needed
        });
    }
    
    // Auto-focus search field if not in booking mode
    if (!bookingMode && searchInput && searchInput.value === '') {
        searchInput.focus();
    }
});