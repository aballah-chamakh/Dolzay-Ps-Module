document.addEventListener('DOMContentLoaded', function() {
    const cities_delegations = {
        "Ariana": ["Ariana Ville", "Soukra", "Raoued", "Ettadhamen", "Mnihla"],
        "Beja": ["Beja Nord", "Beja Sud", "Testour", "Teboursouk", "Medjez El Bab", "Nefza", "Amdoun"],
        "Ben Arous": ["Ben Arous", "Mourouj", "Hammam Lif", "Hammam Chott", "Bou Mhel El Bassatine", "Ezzahra", "Megrine", "Mohamedia", "Fouchana", "Radès", "Mornag"],
        "Bizerte": ["Bizerte Nord", "Bizerte Sud", "Menzel Jemil", "Menzel Bourguiba", "Tinja", "Sejnane", "Ras Jebel", "Ghar El Melh", "El Alia", "Mateur", "Joumine"],
        "Gabes": ["Gabes Ville", "Gabes Sud", "Gabes Ouest", "El Hamma", "Matmata", "Mareth", "Nouvelle Matmata", "Ghannouch", "Menzel Habib"],
        "Gafsa": ["Gafsa Nord", "Gafsa Sud", "Metlaoui", "Redeyef", "Moulares", "El Ksar", "Sened", "Oum Larayes", "Belkhir", "El Guettar"],
        "Jendouba": ["Jendouba", "Bou Salem", "Tabarka", "Ain Draham", "Fernana", "Balta Bou Aouane", "Ghardimaou"],
        "Kairouan": ["Kairouan Nord", "Kairouan Sud", "Chebika", "Oueslatia", "Sbikha", "Hajeb El Ayoun", "Nasrallah", "Bou Hajla", "Cheraitia"],
        "Kasserine": ["Kasserine Nord", "Kasserine Sud", "Thala", "Sbeitla", "Feriana", "Hassi El Ferid", "Sbiba", "Jedelienne", "El Ayoun"],
        "Kebili": ["Kebili Nord", "Kebili Sud", "Douz Nord", "Douz Sud", "Souk Lahad"],
        "La Manouba": ["Manouba", "Douar Hicher", "Oued Ellil", "Den Den", "Mornaguia", "Borj El Amri", "El Batan", "Tebourba"],
        "Le Kef": ["Le Kef", "Dahmani", "Jérissa", "Kalâat Snan", "Kalâat Khasba", "Nebeur", "Sakiet Sidi Youssef", "Tajerouine"],
        "Mahdia": ["Mahdia", "Bou Merdes", "Chebba", "El Jem", "Ksour Essef", "Melloulech", "Sidi Alouane"],
        "Medenine": ["Medenine Nord", "Medenine Sud", "Houmt Souk", "Ajim", "Midoun", "Ben Gardane", "Zarzis", "Beni Khedache"],
        "Monastir": ["Monastir", "Sahline", "Ksibet El Mediouni", "Jemmal", "Zeramdine", "Moknine", "Bekalta", "Teboulba", "Ksar Hellal", "Beni Hassen"],
        "Nabeul": ["Nabeul", "Hammamet", "Korba", "Kelibia", "Dar Chaabane El Fehri", "El Mida", "Beni Khiar", "Menzel Bouzelfa", "Takelsa"],
        "Sfax": ["Sfax Ville", "Sfax Sud", "Sfax Ouest", "Sakiet Ezzit", "Sakiet Eddaier", "Thyna", "El Ain", "Agareb", "Menzel Chaker", "Bir Ali Ben Khalifa"],
        "Sidi Bouzid": ["Sidi Bouzid Ouest", "Sidi Bouzid Est", "Bir El Hafey", "Meknassy", "Mezzouna", "Regueb", "Jilma", "Cebbala Ouled Asker"],
        "Siliana": ["Siliana Nord", "Siliana Sud", "Gaafour", "El Krib", "Bouarada", "Makthar", "Bargou", "Kesra"],
        "Sousse": ["Sousse Ville", "Sousse Jawhara", "Sousse Riadh", "Hammam Sousse", "Akouda", "Kalaa Kebira", "Kalaa Seghira", "Enfidha", "Hergla"],
        "Tataouine": ["Tataouine Nord", "Tataouine Sud", "Remada", "Ghomrassen", "Bir Lahmar", "Dehiba"],
        "Tozeur": ["Tozeur", "Degache", "Nefta", "Hazoua", "Tamerza"],
        "Tunis": ["Bab El Bhar", "Bab Souika", "El Menzah", "El Omrane", "Carthage", "La Marsa", "Le Kram", "La Goulette"],
        "Zaghouan": ["Zaghouan", "Zriba", "Fahs", "Nadhour", "Bir Mcherga", "Saouaf"]
    }

    const citySelect = document.querySelector("select[name='customer_address[city]']");
    const delegationSelect = document.querySelector("select[name='customer_address[delegation]']");
    
    function updateDelegations() {

        // clear the delegations select except the first option
        while (delegationSelect.options.length > 1) {
            delegationSelect.remove(1);
        }

        // add the delegations based on the selected city
        if (citySelect.value) {
            cities_delegations[citySelect.value].forEach(function(delegation) {
                const option = document.createElement('option');
                option.value = delegation;
                option.textContent = delegation;
                delegationSelect.appendChild(option);
            });
        }
        delegationSelect.value = "";
    }

    
    // Update delegations when city changes
    citySelect.addEventListener('change', updateDelegations);
    
});