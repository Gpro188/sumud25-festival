// Main JavaScript file for SUMUD'25 Arts Festival Results System

// Function to confirm deletion actions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Function to toggle visibility of elements
function toggleVisibility(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = element.style.display === 'none' ? 'block' : 'none';
    }
}

// Function to validate form inputs
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const requiredFields = form.querySelectorAll('[required]');
    for (let field of requiredFields) {
        if (!field.value.trim()) {
            alert('Please fill in all required fields.');
            field.focus();
            return false;
        }
    }
    
    return true;
}

// Function to filter programs based on category (for admin results page)
function filterPrograms() {
    const categorySelect = document.getElementById('category');
    const programSelect = document.getElementById('program_id');
    
    if (!categorySelect || !programSelect) return;
    
    const selectedCategory = categorySelect.value;
    const programOptions = programSelect.querySelectorAll('option[data-category]');
    
    programOptions.forEach(option => {
        if (!selectedCategory || option.dataset.category === selectedCategory) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
    console.log('SUMUD\'25 Arts Festival System Loaded');
});