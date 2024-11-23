document.addEventListener('DOMContentLoaded', function() {
    alert("hello !!!!!!!!!!!!!!!")
    const citySelect = document.getElementById('city_select');
    const delegationSelect = document.getElementById('delegation_select');
    
    function updateDelegations() {
        const selectedCity = citySelect.value;
        delegationSelect.innerHTML = '<option value="">' + 
            prestashop.translations.select_delegation + '</option>';
        
        if (selectedCity && cities_delegations[selectedCity]) {
            cities_delegations[selectedCity].forEach(function(delegation) {
                const option = document.createElement('option');
                option.value = delegation;
                option.textContent = delegation;
                if (currentDelegation === delegation) {
                    option.selected = true;
                }
                delegationSelect.appendChild(option);
            });
        }
    }

    // Store initial delegation value if editing
    const currentDelegation = delegationSelect.getAttribute('data-current');
    
    // Update delegations when city changes
    citySelect.addEventListener('change', updateDelegations);
    
    // Initial population of delegations if city is selected
    if (citySelect.value) {
        updateDelegations();
    }
});