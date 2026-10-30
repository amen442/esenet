// ESEN NET - Main JavaScript File
// Global variables and configuration
const APP_CONFIG = {
    apiBaseUrl: '/api',
    toastDuration: 3000,
    animationDuration: 300,
    debounceDelay: 300
};

// Utility functions
const Utils = {
    // Debounce function for search and other input events
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Format date
    formatDate(date) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(date).toLocaleDateString('fr-FR', options);
    },

    // Format number with commas
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },

    // Generate random ID
    generateId() {
        return Math.random().toString(36).substr(2, 9);
    },

    // Check if element is in viewport
    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    },

    // Smooth scroll to element
    scrollToElement(element, offset = 0) {
        const elementPosition = element.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    },

    // Get URL parameters
    getUrlParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }
};

// Toast notification system
class ToastManager {
    constructor() {
        this.container = this.createContainer();
    }

    createContainer() {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
        }
        return container;
    }

    show(message, type = 'info', title = '', duration = APP_CONFIG.toastDuration) {
        const toastId = 'toast-' + Utils.generateId();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${title ? `<strong>${title}</strong><br>` : ''}${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        this.container.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement);
        
        toast.show();
        
        // Auto remove after duration
        setTimeout(() => {
            toastElement.remove();
        }, duration + 500);
    }

    success(message, title = 'Succès') {
        this.show(message, 'success', title);
    }

    error(message, title = 'Erreur') {
        this.show(message, 'danger', title);
    }

    info(message, title = 'Information') {
        this.show(message, 'info', title);
    }

    warning(message, title = 'Attention') {
        this.show(message, 'warning', title);
    }
}

// Global toast manager instance
const toast = new ToastManager();

// Loading spinner manager
class LoadingManager {
    constructor() {
        this.container = this.createContainer();
    }

    createContainer() {
        let container = document.getElementById('loadingContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'loadingContainer';
            container.className = 'loading-overlay position-fixed top-0 start-0 w-100 h-100 d-none';
            container.style.zIndex = '9999';
            container.innerHTML = `
                <div class="d-flex justify-content-center align-items-center h-100 bg-dark bg-opacity-50">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            `;
            document.body.appendChild(container);
        }
        return container;
    }

    show() {
        this.container.classList.remove('d-none');
    }

    hide() {
        this.container.classList.add('d-none');
    }
}

// Global loading manager instance
const loading = new LoadingManager();

// Modal manager
class ModalManager {
    constructor() {
        this.modals = {};
    }

    create(id, title, content, options = {}) {
        const modalHtml = `
            <div class="modal fade" id="${id}" tabindex="-1">
                <div class="modal-dialog ${options.size || ''}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${content}
                        </div>
                        ${options.footer ? `<div class="modal-footer">${options.footer}</div>` : ''}
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        this.modals[id] = new bootstrap.Modal(document.getElementById(id));
        return this.modals[id];
    }

    show(id) {
        if (this.modals[id]) {
            this.modals[id].show();
        }
    }

    hide(id) {
        if (this.modals[id]) {
            this.modals[id].hide();
        }
    }

    destroy(id) {
        if (this.modals[id]) {
            this.modals[id].dispose();
            delete this.modals[id];
            const element = document.getElementById(id);
            if (element) {
                element.remove();
            }
        }
    }
}

// Global modal manager instance
const modal = new ModalManager();

// Form validation
class FormValidator {
    constructor(form) {
        this.form = form;
        this.rules = {};
        this.errors = {};
    }

    addRule(field, rules) {
        this.rules[field] = rules;
        return this;
    }

    validate() {
        this.errors = {};
        let isValid = true;

        Object.keys(this.rules).forEach(field => {
            const element = this.form.querySelector(`[name="${field}"]`);
            if (!element) return;

            const value = element.value.trim();
            const rules = this.rules[field];

            rules.forEach(rule => {
                if (rule.required && !value) {
                    this.addError(field, 'Ce champ est requis');
                    isValid = false;
                } else if (rule.email && value && !this.isValidEmail(value)) {
                    this.addError(field, 'Veuillez entrer une adresse email valide');
                    isValid = false;
                } else if (rule.minLength && value.length < rule.minLength) {
                    this.addError(field, `Minimum ${rule.minLength} caractères requis`);
                    isValid = false;
                } else if (rule.pattern && value && !rule.pattern.test(value)) {
                    this.addError(field, rule.message || 'Format invalide');
                    isValid = false;
                }
            });
        });

        this.displayErrors();
        return isValid;
    }

    addError(field, message) {
        if (!this.errors[field]) {
            this.errors[field] = [];
        }
        this.errors[field].push(message);
    }

    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    displayErrors() {
        // Clear previous errors
        this.form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        this.form.querySelectorAll('.invalid-feedback').forEach(el => {
            el.remove();
        });

        // Display new errors
        Object.keys(this.errors).forEach(field => {
            const element = this.form.querySelector(`[name="${field}"]`);
            if (element) {
                element.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = this.errors[field].join(', ');
                element.parentNode.appendChild(feedback);
            }
        });
    }

    reset() {
        this.errors = {};
        this.form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        this.form.querySelectorAll('.invalid-feedback').forEach(el => {
            el.remove();
        });
    }
}

// Animation utilities
class AnimationUtils {
    static fadeIn(element, duration = APP_CONFIG.animationDuration) {
        element.style.opacity = '0';
        element.style.display = 'block';
        
        let start = null;
        const animate = (timestamp) => {
            if (!start) start = timestamp;
            const progress = timestamp - start;
            
            if (progress < duration) {
                element.style.opacity = progress / duration;
                requestAnimationFrame(animate);
            } else {
                element.style.opacity = '1';
            }
        };
        
        requestAnimationFrame(animate);
    }

    static fadeOut(element, duration = APP_CONFIG.animationDuration) {
        element.style.opacity = '1';
        
        let start = null;
        const animate = (timestamp) => {
            if (!start) start = timestamp;
            const progress = timestamp - start;
            
            if (progress < duration) {
                element.style.opacity = 1 - (progress / duration);
                requestAnimationFrame(animate);
            } else {
                element.style.opacity = '0';
                element.style.display = 'none';
            }
        };
        
        requestAnimationFrame(animate);
    }

    static slideDown(element, duration = APP_CONFIG.animationDuration) {
        element.style.height = '0';
        element.style.overflow = 'hidden';
        element.style.display = 'block';
        
        const targetHeight = element.scrollHeight;
        let start = null;
        
        const animate = (timestamp) => {
            if (!start) start = timestamp;
            const progress = timestamp - start;
            
            if (progress < duration) {
                element.style.height = (progress / duration) * targetHeight + 'px';
                requestAnimationFrame(animate);
            } else {
                element.style.height = 'auto';
                element.style.overflow = 'visible';
            }
        };
        
        requestAnimationFrame(animate);
    }

    static slideUp(element, duration = APP_CONFIG.animationDuration) {
        element.style.height = element.scrollHeight + 'px';
        element.style.overflow = 'hidden';
        
        let start = null;
        const startHeight = element.scrollHeight;
        
        const animate = (timestamp) => {
            if (!start) start = timestamp;
            const progress = timestamp - start;
            
            if (progress < duration) {
                element.style.height = startHeight - (progress / duration) * startHeight + 'px';
                requestAnimationFrame(animate);
            } else {
                element.style.height = '0';
                element.style.display = 'none';
                element.style.overflow = 'visible';
            }
        };
        
        requestAnimationFrame(animate);
    }
}

// Search functionality
class SearchManager {
    constructor(inputSelector, resultsSelector, options = {}) {
        this.input = document.querySelector(inputSelector);
        this.results = document.querySelector(resultsSelector);
        this.options = {
            minQueryLength: 2,
            debounceDelay: APP_CONFIG.debounceDelay,
            ...options
        };
        
        this.init();
    }

    init() {
        if (this.input) {
            this.input.addEventListener('input', Utils.debounce(this.handleSearch.bind(this), this.options.debounceDelay));
            this.input.addEventListener('focus', this.showResults.bind(this));
            document.addEventListener('click', this.handleOutsideClick.bind(this));
        }
    }

    handleSearch(e) {
        const query = e.target.value.trim();
        
        if (query.length < this.options.minQueryLength) {
            this.hideResults();
            return;
        }

        this.performSearch(query);
    }

    performSearch(query) {
        // This would typically make an API call
        // For now, we'll use mock data
        const results = this.mockSearch(query);
        this.displayResults(results);
    }

    mockSearch(query) {
        const mockData = [
            { id: 1, type: 'student', name: 'Sarah Trabelsi', description: 'L3 Data Science' },
            { id: 2, type: 'company', name: 'Telnet', description: 'Société d\'ingénierie informatique' },
            { id: 3, type: 'offer', name: 'Stage Développeur Web', description: 'React, Node.js, MongoDB' },
            { id: 4, type: 'event', name: 'Forum ESEN 2026', description: '10 Mai 2026' }
        ];

        return mockData.filter(item => 
            item.name.toLowerCase().includes(query.toLowerCase()) ||
            item.description.toLowerCase().includes(query.toLowerCase())
        );
    }

    displayResults(results) {
        if (!this.results) return;

        if (results.length === 0) {
            this.results.innerHTML = '<div class="p-3 text-muted">Aucun résultat trouvé</div>';
        } else {
            this.results.innerHTML = results.map(result => `
                <div class="search-result p-3 border-bottom hover:bg-gray-50 cursor-pointer" data-id="${result.id}" data-type="${result.type}">
                    <div class="fw-bold">${result.name}</div>
                    <div class="small text-muted">${result.description}</div>
                </div>
            `).join('');

            // Add click handlers
            this.results.querySelectorAll('.search-result').forEach(item => {
                item.addEventListener('click', this.handleResultClick.bind(this));
            });
        }

        this.showResults();
    }

    handleResultClick(e) {
        const result = e.currentTarget;
        const id = result.dataset.id;
        const type = result.dataset.type;
        
        // Handle different types of results
        switch (type) {
            case 'student':
                window.location.href = `profile.php?id=${id}`;
                break;
            case 'company':
                window.location.href = `company-profile.php?id=${id}`;
                break;
            case 'offer':
                window.location.href = `offers.php#offer-${id}`;
                break;
            case 'event':
                window.location.href = `events.php#event-${id}`;
                break;
        }
        
        this.hideResults();
        this.input.value = '';
    }

    showResults() {
        if (this.results) {
            this.results.style.display = 'block';
        }
    }

    hideResults() {
        if (this.results) {
            this.results.style.display = 'none';
        }
    }

    handleOutsideClick(e) {
        if (!this.input.contains(e.target) && !this.results.contains(e.target)) {
            this.hideResults();
        }
    }
}

// Filter functionality
class FilterManager {
    constructor(filtersSelector, itemsSelector, options = {}) {
        this.filters = document.querySelectorAll(filtersSelector);
        this.items = document.querySelectorAll(itemsSelector);
        this.options = options;
        
        this.init();
    }

    init() {
        this.filters.forEach(filter => {
            filter.addEventListener('change', this.applyFilters.bind(this));
            filter.addEventListener('input', Utils.debounce(this.applyFilters.bind(this), APP_CONFIG.debounceDelay));
        });
    }

    applyFilters() {
        const activeFilters = this.getActiveFilters();
        
        this.items.forEach(item => {
            if (this.itemMatchesFilters(item, activeFilters)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });

        this.updateResultsCount();
    }

    getActiveFilters() {
        const filters = {};
        
        this.filters.forEach(filter => {
            const name = filter.name || filter.id;
            const value = filter.value;
            
            if (filter.type === 'checkbox') {
                if (!filters[name]) filters[name] = [];
                if (filter.checked) {
                    filters[name].push(value);
                }
            } else if (value) {
                filters[name] = value;
            }
        });
        
        return filters;
    }

    itemMatchesFilters(item, filters) {
        // This would typically check item data against filters
        // For now, we'll use a simple implementation
        return true;
    }

    updateResultsCount() {
        const visibleItems = Array.from(this.items).filter(item => item.style.display !== 'none');
        const countElement = document.getElementById('resultsCount') || document.getElementById('eventResultsCount');
        
        if (countElement) {
            countElement.textContent = visibleItems.length;
        }
    }
}

// Counter animation
class CounterAnimation {
    constructor(element, targetValue, duration = 2000) {
        this.element = element;
        this.targetValue = targetValue;
        this.duration = duration;
        this.startValue = 0;
        this.startTime = null;
    }

    start() {
        this.startTime = performance.now();
        this.animate();
    }

    animate() {
        const currentTime = performance.now();
        const elapsed = currentTime - this.startTime;
        const progress = Math.min(elapsed / this.duration, 1);
        
        const currentValue = Math.floor(this.startValue + (this.targetValue - this.startValue) * progress);
        this.element.textContent = Utils.formatNumber(currentValue);
        
        if (progress < 1) {
            requestAnimationFrame(this.animate.bind(this));
        }
    }
}

// Initialize on DOM content loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // Initialize popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

    // Initialize counters
    const counters = document.querySelectorAll('[data-counter]');
    const observerOptions = {
        threshold: 0.5
    };

    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const targetValue = parseInt(entry.target.dataset.counter);
                const counter = new CounterAnimation(entry.target, targetValue);
                counter.start();
                counterObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    counters.forEach(counter => {
        counterObserver.observe(counter);
    });

    // Initialize search if present
    const searchInput = document.querySelector('#searchInput');
    if (searchInput) {
        new SearchManager('#searchInput', '#searchResults');
    }

    // Initialize filters if present
    const filters = document.querySelector('#typeFilter, #sectorFilter, #locationFilter');
    if (filters) {
        new FilterManager('#typeFilter, #sectorFilter, #locationFilter, #dateFilter', '.offer-card, .event-card');
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                Utils.scrollToElement(target, 80);
            }
        });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const validator = new FormValidator(form);
            
            // Add validation rules based on input attributes
            form.querySelectorAll('input, textarea, select').forEach(input => {
                const rules = {};
                
                if (input.hasAttribute('required')) {
                    rules.required = true;
                }
                
                if (input.type === 'email') {
                    rules.email = true;
                }
                
                if (input.hasAttribute('minlength')) {
                    rules.minLength = parseInt(input.getAttribute('minlength'));
                }
                
                if (input.hasAttribute('pattern')) {
                    rules.pattern = new RegExp(input.getAttribute('pattern'));
                    rules.message = input.getAttribute('data-error-message') || 'Format invalide';
                }
                
                if (Object.keys(rules).length > 0) {
                    validator.addRule(input.name || input.id, rules);
                }
            });
            
            if (!validator.validate()) {
                e.preventDefault();
                toast.error('Veuillez corriger les erreurs dans le formulaire');
            }
        });
    });

    // Password strength indicator
    const passwordInputs = document.querySelectorAll('input[type="password"][data-strength]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            updatePasswordStrength(this, strength);
        });
    });

    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector(input.dataset.preview);
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
});

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    return strength;
}

function updatePasswordStrength(input, strength) {
    let strengthText = '';
    let strengthClass = '';
    
    switch(strength) {
        case 0:
        case 1:
            strengthText = 'Faible';
            strengthClass = 'text-danger';
            break;
        case 2:
        case 3:
            strengthText = 'Moyen';
            strengthClass = 'text-warning';
            break;
        case 4:
        case 5:
            strengthText = 'Fort';
            strengthClass = 'text-success';
            break;
    }
    
    let strengthDiv = input.parentNode.querySelector('.password-strength');
    if (!strengthDiv) {
        strengthDiv = document.createElement('div');
        strengthDiv.className = 'password-strength mt-1';
        input.parentNode.appendChild(strengthDiv);
    }
    
    strengthDiv.innerHTML = `<small class="${strengthClass}">Force du mot de passe: ${strengthText}</small>`;
}

// Export global functions for use in other scripts
window.ESEN = {
    Utils,
    toast,
    loading,
    modal,
    FormValidator,
    AnimationUtils,
    SearchManager,
    FilterManager,
    CounterAnimation
};
