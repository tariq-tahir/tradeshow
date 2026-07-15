/**
 * Account Registration Form Functionality
 * Handles state/city dropdowns and payment plan selection
 * 
 * @package awps
 */

export default class AccountRegister {
    constructor() {
        this.form = document.querySelector('.awps-register-form');
        if (!this.form) return;

        this.stateEl = document.getElementById('state');
        this.cityEl = document.getElementById('city');
        this.paymentCards = document.querySelectorAll('.payment-plan-card');
        this.paymentRadios = document.querySelectorAll('input[name="payment_plan"]');
        
        // Get data from form data attributes
        this.citiesData = JSON.parse(this.form.dataset.cities || '{}');
        this.savedCity = this.form.dataset.savedCity || '';

        this.init();
    }

    init() {
        this.initStateCity();
        this.initPaymentPlans();
    }

    // ─────────────────────────────────────────────────────────────
    // State → City Dropdown Logic
    // ─────────────────────────────────────────────────────────────
    initStateCity() {
        if (!this.stateEl || !this.cityEl || !this.citiesData) return;

        const updateCities = () => {
            const state = this.stateEl.value;
            
            // Reset dropdown content
            this.cityEl.innerHTML = '';
            
            // CASE 1: No State Selected → Disable City Dropdown
            if (!state) {
                this.cityEl.disabled = true;
                const defaultOpt = document.createElement('option');
                defaultOpt.value = '';
                defaultOpt.textContent = '— Select State First —';
                this.cityEl.appendChild(defaultOpt);
                return;
            }

            // CASE 2: State Selected → Enable City Dropdown
            this.cityEl.disabled = false;
            
            // Add placeholder
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = '— Select City —';
            this.cityEl.appendChild(placeholder);

            // Populate cities from the data
            const cities = this.citiesData[state] || [];
            cities.forEach((cityName) => {
                const opt = document.createElement('option');
                opt.value = cityName;
                opt.textContent = cityName;
                
                // Restore selection if it matches the saved city
                if (cityName === this.savedCity) {
                    opt.selected = true;
                }
                
                this.cityEl.appendChild(opt);
            });
        };

        // Attach event listener
        this.stateEl.addEventListener('change', updateCities);

        // Run on page load
        updateCities();
    }

    // ─────────────────────────────────────────────────────────────
    // Payment Plan Selection Logic
    // ─────────────────────────────────────────────────────────────
    initPaymentPlans() {
        if (!this.paymentCards.length || !this.paymentRadios.length) return;

        const updateSelectedCard = () => {
            this.paymentCards.forEach((card) => {
                const radio = card.querySelector('input[type="radio"]');
                if (radio.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });
        };

        // Add click listeners to all cards
        this.paymentCards.forEach((card) => {
            card.addEventListener('click', (e) => {
                // Don't trigger if clicking directly on the radio
                if (e.target.tagName === 'INPUT') return;
                
                const radio = card.querySelector('input[type="radio"]');
                radio.checked = true;
                updateSelectedCard();
            });
        });

        // Add change listeners to radios
        this.paymentRadios.forEach((radio) => {
            radio.addEventListener('change', updateSelectedCard);
        });

        // Run on page load to set initial state
        updateSelectedCard();
    }
}