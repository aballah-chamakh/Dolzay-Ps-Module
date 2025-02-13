document.addEventListener('DOMContentLoaded', function() {
    const cities_delegations = {
        "Ariana": ["La Soukra", "Ariana Ville", "Raoued", "Sidi Thabet", "Kalaat Landlous", "Ettadhamen", "Mnihla", "Ennasr"],
        "Beja": ["Amdoun", "Thibar", "Teboursouk", "Beja Nord", "Testour", "Nefza", "Mejez El Bab", "Beja Sud", "Goubellat"],
        "Ben Arous": ["Mornag", "Ben Arous", "Hammam Chatt", "El Mourouj", "Fouchana", "Hammam Lif", "Bou Mhel El Bassatine", "Rades", "Ezzahra", "Mohamadia", "Megrine", "Nouvelle Medina"],
        "Bizerte": ["Bizerte Sud", "Utique", "Ghezala", "Ghar El Melh", "Joumine", "Ras Jebel", "Bizerte Nord", "Mateur", "Menzel Jemil", "Menzel Bourguiba", "Jarzouna", "Sejnane", "Tinja", "El Alia"],
        "Gabes": ["Mareth", "Nouvelle Matmat", "Gabes Ouest", "El Hamma", "Matmata", "Gabes Medina", "Gabes Sud", "El Metouia", "Ghannouche", "Menzel Habib"],
        "Gafsa": ["Sned", "Belkhir", "El Guettar", "El Mdhilla", "Metlaoui", "El Ksar", "Gafsa Sud", "Moulares", "Redeyef", "Sidi Aich", "Gafsa Nord"],
        "Jendouba": ["Ain Draham", "Fernana", "Jendouba", "Tabarka", "Ghardimaou", "Bou Salem", "Balta Bou Aouene", "Jendouba Nord", "Oued Mliz"],
        "Kairouan": ["Chebika", "Sbikha", "Haffouz", "Kairouan Sud", "Oueslatia", "Hajeb El Ayoun", "El Ala", "Bou Hajla", "Cherarda", "Kairouan Nord", "Nasrallah"],
        "Kasserine": ["Haidra", "Jediliane", "Foussana", "Sbiba", "Mejel Bel Abbes", "Feriana", "Kasserine Nord", "Thala", "Kasserine Sud", "Sbeitla", "El Ayoun", "Hassi El Frid"],
        "Kebili": ["Kebili Sud", "Douz", "Souk El Ahad", "El Faouar", "Kebili Nord"],
        "Kef": ["Dahmani", "El Ksour", "Jerissa", "Nebeur", "Sakiet Sidi Youssef", "Kalaat Sinane", "Le Kef Est", "Touiref", "Le Sers", "Tajerouine", "Kalaa El Khasba", "Le Kef Ouest"],
        "Mahdia": ["Hbira", "Sidi Alouene", "El Jem", "Melloulech", "Bou Merdes", "Ouled Chamakh", "Souassi", "Chorbane", "Mahdia", "Ksour Essaf", "La Chebba"],
        "Mannouba": ["Tebourba", "Borj El Amri", "Mornaguia", "Jedaida", "Oued Ellil", "El Battan", "Douar Hicher", "Mannouba"],
        "Medenine": ["Midoun", "Ajim", "Medenine Sud", "Beni Khedache", "Houmet Essouk", "Sidi Makhlouf", "Ben Guerdane", "Zarzis", "Medenine Nord"],
        "Monastir": ["Moknine", "Beni Hassen", "Bekalta", "Bembla", "Ksibet El Medioun", "Jemmal", "Sayada Lamta Bou Hjar", "Ouerdanine", "Ksar Helal", "Monastir", "Teboulba", "Sahline", "Zeramdine"],
        "Nabeul": ["Kelibia", "El Mida", "Nabeul", "Grombalia", "Hammamet", "Dar Chaabane Elfe", "Menzel Bouzelfa", "Bou Argoub", "Menzel Temime", "Korba", "Beni Khalled", "Beni Khiar", "El Haouaria", "Takelsa", "Soliman", "Hammam El Ghez"],
        "Sfax": ["Agareb", "Jebeniana", "Sfax Ville", "Menzel Chaker", "Mahras", "Ghraiba", "El Amra", "Bir Ali Ben Khelifa", "El Hencha", "Esskhira", "Kerkenah", "Sakiet Ezzit", "Sfax Sud", "Sakiet Eddaier", "Sfax Est"],
        "Sidi Bouzid": ["Sidi Bouzid Est", "Jilma", "Ben Oun", "Bir El Haffey", "Sidi Bouzid Ouest", "Regueb", "Menzel Bouzaiene", "Maknassy", "Souk Jedid", "Ouled Haffouz", "Cebbala", "Mezzouna"],
        "Siliana": ["Rohia", "Sidi Bou Rouis", "Siliana Sud", "Bargou", "Bou Arada", "Gaafour", "Kesra", "Makthar", "Le Krib", "El Aroussa", "Siliana Nord"],
        "Sousse": ["Kalaa El Kebira", "Bou Ficha", "Enfidha", "Akouda", "Msaken", "Kondar", "Sidi El Heni", "Sousse Riadh", "Sousse Jaouhara", "Sousse Ville", "Kalaa Essghira", "Hammam Sousse", "Sidi Bou Ali", "Hergla"],
        "Tataouine": ["Tataouine Sud", "Smar", "Remada", "Bir Lahmar", "Dhehiba", "Tataouine Nord", "Ghomrassen"],
        "Tozeur": ["Tozeur", "Tameghza", "Nefta", "Degueche", "Hezoua"],
        "Tunis": ["Sidi El Bechir", "La Marsa", "El Hrairia", "Jebel Jelloud", "Carthage", "Bab Bhar", "La Medina", "Bab Souika", "El Omrane", "El Ouerdia", "El Kram", "Sidi Hassine", "Le Bardo", "La Goulette", "Cite El Khadra", "El Kabbaria", "Tunis centre", "Ezzouhour", "Ettahrir", "El Omrane Superi", "Essijoumi", "Tunis Ville"],
        "Zaghouan": ["Hammam Zriba", "Bir Mcherga", "Ennadhour", "Zaghouan", "El Fahs", "Saouef"]
    };

    const citySelect = document.querySelector("select[name='city']");
    const delegationSelect = document.querySelector("select[name='delegation']");
    
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