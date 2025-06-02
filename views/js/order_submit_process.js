document.addEventListener('DOMContentLoaded', function() {

    //const dz_module_controller_base_url = window.location.href.split('/index.php')[0]+"/dz";
    //const dz_module_controller_base_url = "http://localhost/prestashop/dz_admin/dz" ;
    const urlParams = new URLSearchParams(window.location.search);
    const _token = urlParams.get('_token');
    //const dz_carriers = ["Afex"] ;
    let selectedCarrier = "" ;

    const eventPopupTypesData = {
        info : {icon:`<i class="material-icons" style="color:#101B82" >info</i>`,color:'#101B82'},
        restricted : {icon:`<i class='material-icons' style="color:#D81010" >do_not_disturb_on</i>`,color:'#D81010'},
        error : {icon : `<i class="material-icons" style="color:#D81010" >error</i>`,color:'#D81010'},
        result : {icon:`<i class="material-icons" style="color:#28C20F" >bar_chart</i>`,color:'#28C20F'},
        expired : {icon:`<img src='${dz_module_base_url}/uploads/expired.png' />`,color:'#D81010'}
    }


    function create_the_start_btn(){

        const bottom_bar = document.createElement("div")
        bottom_bar.className = "dz-bottom-bar"
        const header_toolbar = document.querySelector(".header-toolbar")
        bottom_bar.style.width = header_toolbar.offsetWidth+"px" ;

        const start_btn = document.createElement('button')
        start_btn.className="dz-start-btn" ;
        start_btn.innerText = "Commencer" ;

        const actionSelect = document.createElement('select')
        actionSelect.name = "dz-action-select"
        actionSelect.className = "dz-action-select"
        
        const options = [
            { value: 'submit_orders', text: 'Soumettre les commandes' },
            { value: 'monitor_orders', text: 'Suivre les commande' }
        ];

        // 3. Add options to the select
        options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.text;
            actionSelect.appendChild(option);
        });
        //actionSelect.value = "submit_orders"
        start_btn.addEventListener('click', ()=>{
            start_btn.disabled = true 
            
            if(actionSelect.value == "submit_orders"){
                selectCarrierStep.render()
                start_btn.disabled = false 
            }else{
                start_btn.innerHTML += `              
                <div class="spinner-border dz-btn-spinner-white" role="status" >
                    <span class="sr-only">Loading...</span>
                </div>
                `
                Server.launchOmp()
            }
            

        });

        document.querySelector("#order_grid_panel").style.marginBottom = "60px"
        bottom_bar.appendChild(actionSelect)
        bottom_bar.appendChild(start_btn)
        document.body.appendChild(bottom_bar)


        const resizeObserver = new ResizeObserver(entries => {
            // Loop through each observed entry (you could have multiple)
            entries.forEach(entry => {
              // Check the width of the header_toolbar
              bottom_bar.style.width = entry.contentRect.width+"px" ;
            });
        });
        resizeObserver.observe(header_toolbar);
    }


    const Server = {
        launchOsp : function(orderIds,carrier,continueBtn,cancelBtn){
            fetch(dz_module_controller_base_url+"/order_submit_process?_token="+_token, {
                method: 'POST',
                credentials: 'include', // Ensures cookies are sent with the request
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({'order_ids':orderIds,'carrier':carrier})
            })
            .then(response => response.json())
            .then(function (data){
                
                if(data.status == "success"){
                    let process = data.process 
                    if (process.meta_data && (process.meta_data['orders_with_invalid_fields'].length || process.meta_data['already_submitted_orders'].length)){
                        alreadySubmittedAndInvalidOrdersStep.render(process)
                    }else if(!process.result.error_message){
                        process['items_to_process_cnt'] = orderIds.length
                        popup.close()
                        buttons = [
                            {
                                'name' : 'Détails',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                    let process_detail_url = dz_module_controller_base_url+"/order_submit_process/"+process.id+"/?_token="+_token
                                    window.open(process_detail_url,"_blank")
                                    eventPopup.close();
                                }
                            }
                        ]
                        eventPopup.open("result", 
                                        "Résultat",
                                        `<span class="dz-success-badge">${process.result.submitted_orders_cnt}/${process.items_to_process_cnt}</span> commandes ont été soumises avec succès à ${process.carrier} et <span class="dz-error-badge">${process.result.orders_with_errors_cnt}/${process.items_to_process_cnt}</span> commandes ont des erreurs.`,
                                        buttons)
                    }else{
                        popup.close()
                        console.log(process)
                        buttons = [
                            {
                                'name' : 'Détail',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                    let process_detail_url = dz_module_controller_base_url+"/order_submit_process/"+process.id+"/?_token="+_token
                                    window.open(process_detail_url,"_blank")
                                    eventPopup.close();
                                }
                            }
                        ]
                        eventPopup.open("error","Erreur",process.result.error_message,buttons)
                    }
                }else if(data.status == 'conflict'){ // handle the case of an exsint osp running
                    let process = data.process
                    if(process.status == "Initié"){
                        if (process.meta_data && (process.meta_data['orders_with_invalid_fields'].length || process.meta_data['already_submitted_orders'].length)){
                            // show the existingRunningOsp interface and after 3s show the alreadySubmittedAndInvalidOrdersStep interface
                            // in order to inform the user that there is an existing process running
                            existingRunningOspStep.render()
                            setTimeout(()=>{
                                alreadySubmittedAndInvalidOrdersStep.render(process)
                            },3000)
                        }else{
                            existingRunningOspStep.render()
                            setTimeout(()=>{Server.launchOsp(orderIds,carrier,continueBtn,cancelBtn)},3000)
                        }
                    }else{
                        // show the existingRunningOsp interface and after 3s show the progressOfSubmittingOrdersStep interface
                        // in order to inform the user that there is an existing process running
                        existingRunningOspStep.render()
                        setTimeout(()=>{
                            progressOfSubmittingOrdersStep.render(process)
                        },3000)
                    }
                }else if(data.status == 'expired'){
                    popup.close()
                    buttons = [
                        {
                            'name' : 'Ok',
                            'className' : "dz-event-popup-btn",
                            'clickHandler' : function(){
                                            eventPopup.close();
                            }
                        }
                    ]
                    eventPopup.open("expired", 
                                    "Expiration de la période d'essai",
                                    "Votre période d'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.",
                                    buttons)
                }else{
                    continueBtn.disabled = false 
                    continueBtn.innerHTML = "Continuer"
                    cancelBtn.disabled = false 
                }

            })
            .catch(error => {
                continueBtn.disabled = false 
                continueBtn.innerHTML = "Continuer"
                cancelBtn.disabled = false 
                console.error('Error:', error)

            });
        },
        continueOsp : function(process_id,ordersToResubmitIds,continueBtn,cancelBtn){
            let url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/continue?_token="+_token
            console.log("url : "+url)
            console.log(ordersToResubmitIds)
            fetch(url, {
                method: 'PUT',
                credentials: 'include', // Ensures cookies are sent with the request
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({'order_ids':ordersToResubmitIds})
            })
            .then(response => response.json())
            .then(function (data){
                let process = data.process 
                if(data.status == "success"){
                    if (process.status == "Annulé automatiquement"){
                        popup.close()
                        buttons = [
                            {
                                'name' : 'Ok',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                    eventPopup.close()
                                }
                            }
                        ]
                        let message = `Ce processus a été annulé, car il n'a aucune commande valide à soumettre.`
                        
                        eventPopup.open("info","Information",message,buttons)
                    }else{
                        // handle the termination of the osp
                        if(!process.result.error_message){
                            popup.close()
                            buttons = [
                                {
                                    'name' : 'Détail',
                                    'className' : "dz-event-popup-btn",
                                    'clickHandler' : function(){
                                        let process_detail_url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/?_token="+_token
                                        window.open(process_detail_url,"_blank")
                                        eventPopup.close();
                                    }
                                }
                            ]
                            eventPopup.open("result","Résultat",`<span class="dz-success-badge">${process.result.submitted_orders_cnt}/${process.items_to_process_cnt}</span> commandes ont été soumises avec succès à ${process.carrier} et <span class="dz-error-badge">${process.result.orders_with_errors_cnt}/${process.items_to_process_cnt}</span> commandes ont des erreurs.`,buttons)
                        }else{
                            popup.close()
                            buttons = [
                                {
                                    'name' : 'Détail',
                                    'className' : "dz-event-popup-btn",
                                    'clickHandler' : function(){
                                        let process_detail_url = dz_module_controller_base_url+"/order_submit_process/"+process.id+"/?_token="+_token
                                        window.open(process_detail_url,"_blank")
                                        eventPopup.close();
                                    }
                                }
                            ]
                            eventPopup.open("error","Erreur",process.result.error_message,buttons)
                        }
                    }
                }else if(data.status == "conflict"){
                    if(process.status == "Actif" || process.status == "Pre-terminé par l'utilisateur"){
                        progressOfSubmittingOrdersStep.render(process,true)
                    }else{
                        popup.close()
                        buttons = [
                            {
                                'name' : 'Détail',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                    let process_detail_url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/?_token="+_token
                                    window.open(process_detail_url,"_blank")
                                    eventPopup.close();
                                }
                            }
                        ]
                        let message = ""
                        if (process.status == "Annulé par l'utilisateur"){
                            message = "Ce processus a été annulé par un autre utilisateur."
                        }else{
                            message = `Ce processus a été poursuivi par un autre utilisateur, et son état actuel est : ${process.status}.`
                        }
                        eventPopup.open("info","Information",message,buttons)
                    }

                }else{
                    continueBtn.disabled = false
                    continueBtn.innerHTML = "Continuer"
                    cancelBtn.disabled = false 
                }
            })
            .catch(error => {
                continueBtn.disabled = false
                continueBtn.innerHTML = "Continuer"
                cancelBtn.disabled = false 
                console.error('Error:', error)});
        },
        cancelOsp : function(process_id,cancelBtn,continueBtn){
            let url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/cancel?_token="+_token
            console.log("url : "+url)
            fetch(url, {
                method: 'PUT',
                credentials: 'include' // Ensures cookies are sent with the request
            })
            .then(response => response.json())
            .then(function (data){
                if(data.status == "success"){
                    popup.close();
                }else if(data.status == "conflict"){
                    let process = data.process
                    if(process.status == "Actif" || process.status == "Pre-terminé par l'utilisateur"){
                        progressOfSubmittingOrdersStep.render(process,true)
                    }else{
                        popup.close()
                        buttons = [
                            {
                                'name' : 'Détail',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                    let process_detail_url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/?_token="+_token
                                    window.open(process_detail_url,"_blank")
                                    eventPopup.close();
                                }
                            }
                        ]

                        let message = ""
                        if (process.status == "Annulé par l'utilisateur"){
                            message = "Ce processus a été annulé par un autre utilisateur."
                        }else{
                            message = `Ce processus a été poursuivi par un autre utilisateur, et son état actuel est : ${process.status}.`
                        }
                        eventPopup.open("info","Information",message,buttons)
                    }
                }else{
                    cancelBtn.disabled = false 
                    continueBtn.disabled = false 
                }

            })
            .catch(error =>{ 
                console.error('Error:', error)
                cancelBtn.disabled = false
                cancelBtn.innerHTML = "Annuler"
                continueBtn.disabled = false 
            });
        },
        terminateOsp : function(process_id,terminateBtn){
            // note : the monitoring is the one reponsible for confirming the termination of the process or informing
            // the user about a conflict 
            let url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/terminate?_token="+_token
            console.log("url : "+url)
            fetch(url, {
                method: 'PUT',
                credentials: 'include' // Ensures cookies are sent with the request
            })
            .then(response => response.json())
            .then(function(data){
                if(data.status == "success"){
                    console.log("terminate request was done")
                }else if(data.status == "conflict"){
                    console.log(`the process did stop by this status : ${data.process_status}`)
                }
            })
            .catch(error =>{ 
                console.error('Error:', error)
                terminateBtn.disabled = false
                terminateBtn.innerHTML = "Arrêter"
            });
        },
        monitorOsp : function(process_id){
            let url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/monitor?_token="+_token
            console.log("url : "+url)
            fetch(url, {
                method: 'GET',
                credentials: 'include' // Ensures cookies are sent with the request
            })
            .then(response => response.json())
            .then(function(data){

                if(data.status == "success"){
                    let process = data.process
                    // update the interfece with the progress of the osp
                    let submittedOrdersCntEl = popup.popupBodyEl.querySelector(".dz-submitted-orders-cnt")
                    console.log(submittedOrdersCntEl)
                    submittedOrdersCntEl.innerText = process.processed_items_cnt

                    // handle final statuses
                    if(process.status == "Terminé" ){
                        popup.close()
                        buttons = [
                            {
                                'name' : 'Détail',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                    let process_detail_url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/?_token="+_token
                                    window.open(process_detail_url,"_blank")
                                    eventPopup.close();
                                }
                            }
                        ]
    
                        let message = `<span class="dz-success-badge">${process.processed_items_cnt}/${process.items_to_process_cnt}</span> commandes ont été soumises avec succès à ${process.carrier} et <span class="dz-error-badge">${process.items_to_process_cnt-process.processed_items_cnt}/${process.items_to_process_cnt}</span> commandes ont des erreurs.`
                        eventPopup.open("result","Résultat",message,buttons)
                    }else if(process.status == "Terminé par l'utilisateur"){
                        popup.close()

                        buttons = [
                            {
                                'name' : 'Détail',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){

                                    let process_detail_url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/?_token="+_token
                                    window.open(process_detail_url,"_blank")
                                    eventPopup.close();
                                }
                            }
                        ]

                        let message = "Le processus de soumission des commandes a bien été arrêté sans soumettre aucune commande."
                        if(process.processed_items_cnt > 0){
                            message =`Le processus de soumission des commandes a bien été arrêté après la soumission de ${process.processed_items_cnt}/${process.items_to_process_cnt} commandes à ${process.carrier}.`
                        }
                        eventPopup.open("result","Résultat",message,buttons)
                    }else if (process.status == "Interrompu"){
                        popup.close()
                        console.log(process)
                        buttons = [
                            {
                                'name' : 'Détail',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                    let process_detail_url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/?_token="+_token
                                    window.open(process_detail_url,"_blank")
                                    eventPopup.close();
                                }
                            }
                        ]
                        eventPopup.open("error","Erreur",process.error.message,buttons)
                    }else{
                        setTimeout(function(){
                            Server.monitorOsp(process_id);
                        },3000)
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        },
        launchOmp : function(){
            const start_btn = document.querySelector(".dz-start-btn")

            fetch(dz_module_controller_base_url+"/order_monitoring_process?_token="+_token, {
                method: 'POST',
                credentials: 'include', // Ensures cookies are sent with the request
            })
            .then(response => response.json())
            .then((data)=>{

                    if(data.status == "success"){
                        // handle the finish of the omp
                        let process = data.process
                        if(!process.result.error_message){
                            popup.close()
                            buttons = [
                                {
                                    'name' : 'Détail',
                                    'className' : "dz-event-popup-btn",
                                    'clickHandler' : function(){
                                        let process_detail_url = dz_module_controller_base_url+"/order_monitoring_process/"+process.id+"/?_token="+_token
                                        window.open(process_detail_url,"_blank")
                                        eventPopup.close();
                                    }
                                }
                            ]
                            eventPopup.open("result","Résultat",`<span class="dz-success-badge">${process.result.monitored_orders_cnt}/${process.items_to_process_cnt}</span> commandes ont été suivies avec succès et <span class="dz-error-badge">${process.result.orders_with_errors_cnt}/${process.items_to_process_cnt}</span> commandes ont des erreurs.`,buttons)
                        }else {
                            popup.close()
                            console.log(process)
                            buttons = [
                                {
                                    'name' : 'Détail',
                                    'className' : "dz-event-popup-btn",
                                    'clickHandler' : function(){
                                        let process_detail_url = dz_module_controller_base_url+"/order_monitoring_process/"+process.id+"/?_token="+_token
                                        window.open(process_detail_url,"_blank")
                                        eventPopup.close();
                                    }
                                }
                            ]
                            eventPopup.open("error","Erreur",process.result.error_message,buttons)
                        }
                    }else if(data.status == "no_orders_to_monitor"){
                        buttons = [
                            {
                                'name' : 'Ok',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                    eventPopup.close();
                                }
                            }
                        ]
                        eventPopup.open("info", 
                                        "Aucune commande à suivre",
                                        "Il n'y a aucune commande à suivre pour le moment.",
                                        buttons)
                    }else if(data.status == "expired"){
                        buttons = [
                            {
                                'name' : 'Ok',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                                eventPopup.close();
                                }
                            }
                        ]
                        eventPopup.open("expired", 
                                        "Expiration de la période d'essai",
                                        "Votre période d'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.",
                                        buttons)
                    }
                    start_btn.innerHTML="Commencer"
                    start_btn.disabled = false 
            }).catch((error) => {
                start_btn.innerHTML="Commencer"
                start_btn.disabled = false 
                console.error('Error:', error)
            });
        },
        monitorOmp : function(process_id){
            let url = dz_module_controller_base_url+"/order_monitoring_process/"+process_id+"/monitor?_token="+_token

            fetch(url, {
                method: 'GET',
                credentials: 'include' // Ensures cookies are sent with the request
            })
            .then(response => response.json())
            .then(function(data){

                if(data.status == "success"){
                    let process = data.process
                    // update the interfece with the progress of the osp
                    let monitoredOrdersCntEl = popup.popupBodyEl.querySelector(".dz-monitored-orders-cnt")
                    monitoredOrdersCntEl.innerText = process.processed_items_cnt

                    // handle final statuses
                    if(process.status == "Terminé" ){
                        popup.close()
                        buttons = [
                            {
                                'name' : 'Détail',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                    let process_detail_url = dz_module_controller_base_url+"/order_monitoring_process/"+process_id+"/?_token="+_token
                                    window.open(process_detail_url,"_blank")
                                    eventPopup.close();
                                }
                            }
                        ]
    
                        let message = `<span class="dz-success-badge">${process.processed_items_cnt}/${process.items_to_process_cnt}</span> commandes ont été suivies avec succès et <span class="dz-error-badge">${process.items_to_process_cnt-process.processed_items_cnt}/${process.items_to_process_cnt}</span> commandes ont des erreurs.`
                        eventPopup.open("result","Résultat",message,buttons)
                    }else if (process.status == "Interrompu"){
                        popup.close()
                        console.log(process)
                        buttons = [
                            {
                                'name' : 'Détail',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                    let process_detail_url = dz_module_controller_base_url+"/order_monitoring_process/"+process_id+"/?_token="+_token
                                    window.open(process_detail_url,"_blank")
                                    eventPopup.close();
                                }
                            }
                        ]
                        eventPopup.open("error","Erreur",process.error.message,buttons)
                    }else{
                        setTimeout(function(){
                            Server.monitorOmp(process_id);
                        },3000)
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        },
        monitorNotifications : function(){
            let url = dz_module_controller_base_url+"/notifications/list?_token="+_token+"&notif_type=process&page_nb=1&batch_size=20"
            fetch(url, {
                method: 'GET',
                credentials: 'include' // Ensures cookies are sent with the request
            }).then(response => response.json())
            .then(function(data){
                if(data.status == "success"){
                    data.data.notifications.map((idx,notification)=>{
                        buttons = [
                            {
                                'name' : 'Ok',
                                'className' : "dz-event-popup-btn",
                                'clickHandler' : function(){
                                    eventPopup.close();
                                }
                            }
                        ]
                        eventPopup.open(notification.type,notification.title,notification.message,buttons)
                    })
                    setTimeout(()=>{
                        Server.monitorNotifications()
                    },3000)
                }
            })
        }

    }

    const popupOverlay = {
        popupOverlayEl : null,
        create : function(){
            this.popupOverlayEl = document.createElement('div');
            this.popupOverlayEl.className = "dz-popup-overlay";
            document.body.appendChild(this.popupOverlayEl);
        },
        show : function(){
            this.popupOverlayEl.classList.add('dz-show')
        },
        hide : function(){
            this.popupOverlayEl.classList.remove('dz-show')
        }
    }


    const eventPopup = {
        popupEl : null,
        popupHeaderEl : null,
        popupBodyEl : null,
        popupFooterEl : null,
        create : function(){
            this.popupEl = document.createElement("div")
            this.popupEl.className = "dz-event-popup";
            
            this.popupHeaderEl = document.createElement('div') ;
            this.popupHeaderEl.className = "dz-event-popup-header";
            this.popupHeaderEl.innerHTML = `<p></p>
                                            <i class="material-icons">close</i>`
            this.popupHeaderEl.lastElementChild.addEventListener('click',()=>{this.close()})
            this.popupEl.append(this.popupHeaderEl)
            
            this.popupBodyEl = document.createElement('div') ;
            this.popupBodyEl.className = "dz-event-popup-body";
            this.popupEl.append(this.popupBodyEl)
            
            this.popupFooterEl = document.createElement('div') ;
            this.popupFooterEl.className = "dz-event-popup-footer";
            this.popupEl.append(this.popupFooterEl)

            document.body.append(this.popupEl)
        },
        addButtons : function(buttons,color){
            this.popupFooterEl.innerHTML="";
            buttons.forEach((button) => {
                const buttonEl = document.createElement('button');
                buttonEl.textContent = button.name ;
                buttonEl.className = button.className ;
                buttonEl.style.backgroundColor = color ;
                buttonEl.addEventListener('click',button.clickHandler);
                this.popupFooterEl.appendChild(buttonEl);
            });
            
        },
        open : function(type,title,message,buttons) {
            setTimeout(() => {
                popupOverlay.show()
                console.log(this)
                this.popupEl.classList.add('dz-show');

                this.popupHeaderEl.firstElementChild.innerText = title ;
                this.popupHeaderEl.style.backgroundColor = eventPopupTypesData[type].color ;
               
                this.popupBodyEl.innerHTML = ` 
                    ${eventPopupTypesData[type].icon}
                    <p>${message}</p>
                `

                this.addButtons(buttons,eventPopupTypesData[type].color)
            }, 600);
        },
        close : function(){
            setTimeout(() => {
                popupOverlay.hide()
                this.popupFooterEl.innerHTML = "" ;
                this.popupEl.classList.remove('dz-show');
            }, 300);
        },
    }

    const popup = {
        popupEl : null,
        popupHeaderEl : null,
        popupBodyEl : null,
        popupFooterEl : null,

        create : function(){
            this.popupEl = document.createElement("div")
            this.popupEl.id = "dz-popup";
            this.popupEl.className = "dz-popup";
            
            this.popupHeaderEl = document.createElement('div') ;
            this.popupHeaderEl.className = "dz-popup-header";
            this.popupEl.append(this.popupHeaderEl)
            
            this.popupBodyEl = document.createElement('div') ;
            this.popupBodyEl.className = "dz-popup-body";
            this.popupEl.append(this.popupBodyEl)
            
            this.popupFooterEl = document.createElement('div') ;
            this.popupFooterEl.className = "dz-popup-footer";
            this.popupEl.append(this.popupFooterEl)

            document.body.append(this.popupEl)
        },
        open : function() {
            setTimeout(() => {
                this.popupEl.classList.add('dz-show');
                popupOverlay.show()
            }, 10);
        },
        close : function(){
            setTimeout(() => {
                this.popupEl.classList.remove('dz-show');
                popupOverlay.hide();
            }, 300);
        },
        addButtons : function(buttons){
            
            buttons.forEach((button) => {
                const buttonEl = document.createElement('button');
                buttonEl.textContent = button.name ;
                buttonEl.className = button.className ;
                buttonEl.addEventListener('click',button.clickHandler);
                this.popupFooterEl.appendChild(buttonEl);
            });
            
        }
    }

    const paymentPopup = {
        render : function(){
            popup.popupHeaderEl.innerText = "Expiration de la période d'essai"
            popup.popupBodyEl.innerHTML = ""
            popup.popupFooterEl.innerHTML = ""
        }
    }

    const existingRunningOspStep = {
        render : function(){
            popup.popupHeaderEl.innerText = "Un processus de soumission des commandes est en cours."        
            popup.popupBodyEl.innerHTML = `
                <div class="progress-of-submitting-orders-step" >
                    <div class="spinner-border dz-spinner" role="status" >
                    <span class="sr-only">Loading...</span>
                    </div>
                    <p>Un processus de soumission des commandes est en cours. Veuillez patienter quelques secondes pour afficher son statut.</p>
                </div>
            `
            popup.popupFooterEl.innerHTML = ''
            popup.open()
        } 
    }

    const selectCarrierStep = {
        orderIds : [],
        render : function(){

            this.orderIds = this.getSelectedOrderIds() 
            console.log(this.orderIds)
    
            if(this.orderIds.length == 0){
                return;
            }
    
            popup.popupHeaderEl.innerText = "Selectionner un transporteur"
            popup.popupBodyEl.innerHTML = ""
            popup.popupFooterEl.innerHTML = ""

            // create the selectCarrierStep container 

            const selectCarrierStepContainer = document.createElement("div")
            selectCarrierStepContainer.className = "dz-select-carrier-container"
    
            // create the carrier icon  
            const carrierIcon = document.createElement('img');
            carrierIcon.className = "dz-carrier-icon" 
            carrierIcon.src = dz_module_base_url+"/uploads/carrier_icon.png";
            selectCarrierStepContainer.appendChild(carrierIcon);
    
            // create the carrier select 
            const select = document.createElement('select');
            select.className = "dz-carrier-select"
            for(let i=0 ;i<dz_carriers.length;i++){
                let option = document.createElement('option') ;
                option.value = dz_carriers[i].name ;
                option.innerText = dz_carriers[i].name ;
                if(i == 0){
                    option.selected = true ;
                }
                select.appendChild(option);
            }
            selectCarrierStepContainer.appendChild(select);

            popup.popupBodyEl.appendChild(selectCarrierStepContainer);
            const buttons = [
                {
                    'name' : 'Annuler',
                    'className' : "dz-cancel-btn",
                    'clickHandler' : function(){selectCarrierStep.cancel(this)}
                },
                {
                    'name' : 'Continuer',
                    'className' : "dz-continue-btn",
                    'clickHandler' : function(){selectCarrierStep.continue(this)}
                }
            ]
            popup.addButtons(buttons);
    
            // open the popup 
            popup.open()
        },
        getSelectedOrderIds : function(){
            const checkedCheckboxes = document.querySelectorAll('.js-bulk-action-checkbox:checked');
            const orderIds = Array.from(checkedCheckboxes).map(checkbox => parseInt(checkbox.value));
            return orderIds ;
        },
        continue : function(continueBtn){
            // disable the button then add to it a spinner
            continueBtn.disabled = true 
            let cancelBtn = document.querySelector(".dz-cancel-btn")
            cancelBtn.disabled = true 
            continueBtn.innerHTML += `              
                <div class="spinner-border dz-btn-spinner-white" role="status" >
                    <span class="sr-only">Loading...</span>
                </div>
            `
            selectedCarrier = document.querySelector(".dz-carrier-select").value;

            // make a request to initialize the order submit process 
            Server.launchOsp(this.orderIds,selectedCarrier,continueBtn,cancelBtn)
        },
        cancel : function(cancelBtn){
            cancelBtn.disabled = true 
            let continueBtn = document.querySelector(".dz-continue-btn")
            continueBtn.disabled = true 
            popup.close();
        }
    }

    const alreadySubmittedAndInvalidOrdersStep = {

        render : function(process){
            popup.popupHeaderEl.innerText = "Choisir les commandes à re-soumettre et fixer les commandes invalides"
            popup.popupBodyEl.innerHTML = ''
            popup.popupFooterEl.innerHTML = ''
            popup.popupFooterEl.style.display = 'flex'

            const alreadySubmittedAndInvalidOrdersStepContainer = document.createElement("div")
            alreadySubmittedAndInvalidOrdersStepContainer.className = "already-submitted-and-invalid-orders-step-container"
            
            // add the already submitted orders
            if(process.meta_data['already_submitted_orders'] && process.meta_data['already_submitted_orders'].length){
                let already_submitted_orders = process.meta_data.already_submitted_orders
                let table_rows = ""            
                for (let i = 0; i < already_submitted_orders.length; i++) {
                    let currentOrderLink = this.getOrderLink(already_submitted_orders[i].order_id)
                    table_rows += `
                        <tr>
                            <th scope="row">
                                <label class="dz-checkbox-container">
                                    <input type="checkbox" class="dz-submitted-order-checkbox" aria-label="Checkbox" data-order-id=${already_submitted_orders[i].order_id} >
                                    <span class="dz-checkmark"></span>
                                </label>
                            </th>
                            <td>${already_submitted_orders[i].order_id}</td>
                            <td>${already_submitted_orders[i].fullname}</td>
                            <td><a href="${currentOrderLink}" target="_blank" class="dz-order-link"><i class="material-icons">remove_red_eye</i></a></td>
                        </tr>
                    `;
                }
    
                alreadySubmittedAndInvalidOrdersStepContainer.innerHTML += `
                <div class="dz-meta-data-container">
                    <div class="dz-meta-data-header">Les commandes déjà soumises</div>
                    <table class="table dz-meta-data-table">
                        <thead>
                            <tr>
                                <th scope="col">
                                    <label class="dz-checkbox-container">
                                        <input type="checkbox" class="dz-head-submitted-order-checkbox" aria-label="Checkbox">
                                        <span class="dz-checkmark"></span>
                                    </label>
                                </th>
                                <th scope="col">id commande</th>
                                <th scope="col">client</th>
                                <th scope="col">détail</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${table_rows}
                        </tbody>
                    </table>
                </div>`

            }
            // add the already submitted orders
            if(process.meta_data['orders_with_invalid_fields'] && process.meta_data['orders_with_invalid_fields'].length){
                let orders_with_invalid_fields = process.meta_data.orders_with_invalid_fields
                
                let table_rows = ""            
                for (let i = 0; i < orders_with_invalid_fields.length; i++) {
                    let order_link = this.getOrderLink(orders_with_invalid_fields[i].order_id)
                    
                    // get invalid fields 
                    let invalid_fields  = "<div class='dz-invalid-field-badge-container'/>"
                    for(let ii=0;ii<orders_with_invalid_fields[i].invalid_fields.length;ii++){
                        invalid_fields +=  `<span class='dz-invalid-field-badge'>${orders_with_invalid_fields[i].invalid_fields[ii]}</span>`
                    }
                    invalid_fields += "</div>"
                    
                    table_rows += `
                        <tr>
                            <th scope="row">
                                <label class="dz-checkbox-container">
                                    <input class="dz-invalid-order-checkbox" type="checkbox" aria-label="Checkbox">
                                    <span class="dz-checkmark"></span>
                                </label>
                            </th>
                            <td>${orders_with_invalid_fields[i].order_id}</td>
                            <td>${invalid_fields}</td>
                            <td><a href="${order_link}" target="_blank" class="dz-order-link"><i class="material-icons">remove_red_eye</i></a></td>
                        </tr>
                    `;
                }
    
                alreadySubmittedAndInvalidOrdersStepContainer.innerHTML += `
                <div class="dz-meta-data-container">
                    <div class="dz-meta-data-header">Les commandes avec des champs invalides</div>
                    <table class="table dz-meta-data-table">
                        <thead>
                            <tr>
                                <th scope="col">
                                    <label class="dz-checkbox-container">
                                        <input type="checkbox" class="dz-head-invalid-order-checkbox" aria-label="Checkbox">
                                        <span class="dz-checkmark"></span>
                                    </label>
                                </th>
                                <th scope="col">id commande</th>
                                <th scope="col">les champs invalides</th>
                                <th scope="col">détail</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${table_rows}
                        </tbody>
                    </table>
                </div>`

                // add click handlers for the checkboxes of invalid orders
                let headInvalidOrderCheckbox = alreadySubmittedAndInvalidOrdersStepContainer.querySelector(".dz-head-invalid-order-checkbox");
                headInvalidOrderCheckbox.addEventListener('click',function(e){alreadySubmittedAndInvalidOrdersStep.handleHeadCheckbox(e)})
            }


            // add click handlers for the checkboxes of submitted orders
            // note : i have to attach these click event listener after the content of popupBodyEl is fully set 
            // because the listeners added in the first .innerHTML+= will be deleted in the second .innerHTML+= because 
            // the nodes of the first dom update they will be recreated in the second dom update since .innerHTML value is just a string and not nodes  
            let headSubmittedOrderCheckbox = alreadySubmittedAndInvalidOrdersStepContainer.querySelector(".dz-head-submitted-order-checkbox");
            if(headSubmittedOrderCheckbox){
                headSubmittedOrderCheckbox.addEventListener('click',function(e){alreadySubmittedAndInvalidOrdersStep.handleHeadCheckbox(e)})
            }

            popup.popupBodyEl.appendChild(alreadySubmittedAndInvalidOrdersStepContainer)


            // add the cancel and the continue buttons 
            const buttons = [
                {
                    'name' : 'Annuler',
                    'className' : "dz-cancel-btn",
                    'clickHandler' : function (){alreadySubmittedAndInvalidOrdersStep.cancel(this,process['id'])}
                },
                {
                    'name' : 'Continuer',
                    'className' : "dz-continue-btn",
                    'clickHandler' : function (){alreadySubmittedAndInvalidOrdersStep.continue(this,process['id'])}
                }
            ]
            popup.addButtons(buttons);
        },
        continue : function(continueBtn,process_id){ 
            // disable the button then add to it a spinner
            continueBtn.disabled=true; 
            let cancelBtn = document.querySelector(".dz-cancel-btn")  
            cancelBtn.disabled = true ;      
            continueBtn.innerHTML += `              
                <div class="spinner-border dz-btn-spinner-white" role="status" >
                    <span class="sr-only">Loading...</span>
                </div>
            `
            let ordersToResubmitIds = this.getOrdersToResubmitIds()
            let url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/continue?_token="+_token
            console.log("url : "+url)
            Server.continueOsp(process_id,ordersToResubmitIds,continueBtn,cancelBtn)

        },
        cancel : function(cancelBtn,process_id){
            // disable the button then add to it a spinner
            cancelBtn.disabled=true
            let continueBtn = document.querySelector(".dz-continue-btn")
            continueBtn.disabled = true 
            cancelBtn.innerHTML += `              
                <div class="spinner-border dz-btn-spinner-blue" role="status" >
                    <span class="sr-only">Loading...</span>
                </div>
            `

            let url = dz_module_controller_base_url+"/order_submit_process/"+process_id+"/cancel?_token="+_token
            console.log("url : "+url)
            Server.cancelOsp(process_id,cancelBtn,continueBtn);
        },
        getOrderLink : function(order_id){
            let splitted_url = window.location.href.split("?")
            splitted_url[0] +=  order_id+"/view?"
            return splitted_url[0]+splitted_url[1]
        },
        handleHeadCheckbox : function(e){
            let className =  e.target.className ;
            let checked = e.target.checked ;
            if(className.includes("head")){
                let checkboxType = className.split('-')[2]
                let checkboxes = Array.from(document.querySelectorAll(".dz-"+checkboxType+"-order-checkbox"))
                checkboxes.map((el)=>{
                    el.checked = checked ;
                })
            }
        },
        getOrdersToResubmitIds : function(){
            let checkedCheckboxes =document.querySelectorAll(".dz-submitted-order-checkbox:checked")
            let OrdersToResubmitIds = Array.from(checkedCheckboxes,(checkbox)=>checkbox.dataset.orderId)
            return OrdersToResubmitIds
        }
    }

    const progressOfSubmittingOrdersStep = {
        interval : null,
        render : function(process,conflict){
            popup.popupHeaderEl.innerText = "Progrès de la soumission des commandes"        
            popup.popupBodyEl.innerHTML = `
                ${conflict ? "<p class='dz-notice-bar'>Cette soumission a été continuée par un autre utilisateur.</p>" : ""}
                <div class="progress-of-submitting-orders-step" >
                    <div class="spinner-border dz-spinner" role="status" >
                    <span class="sr-only">Loading...</span>
                    </div>
                    <p style="position:relative"><span class="dz-submitted-orders-cnt">${process.processed_items_cnt ? process.processed_items_cnt : 0}</span>/<span class="orders-to-submit-cnt">${process.items_to_process_cnt}</span> commandes ont été soumises à <span class="dz-carrier">${process.carrier ? process.carrier : selectedCarrier}</span></p>
                </div>
                `
            popup.popupFooterEl.innerHTML = ''

            // add the cancel and the continue buttons 
            const buttons = [
                {
                    'name' : 'Arrêter',
                    'className' : "dz-terminate-btn",
                    'clickHandler' : function (){progressOfSubmittingOrdersStep.terminate(this,process.id)}
                }
            ]

            //popup.addButtons(buttons);

            // here i have to put a set interval that periodically pulls the state of the order submit process
            // also i have to caputure the value of the interval to clear it later once the process is completed or has an error or was terminated by the user
            Server.monitorOsp(process.id);

        },
        terminate : function(terminateBtn,process_id){
            console.log("terminate the order submit process")
            
            // disable the button then add to it a spinner
            terminateBtn.disabled=true;           
            terminateBtn.innerHTML += `              
                <div class="spinner-border dz-btn-spinner-white" role="status" >
                    <span class="sr-only">Loading...</span>
                </div>
            `
            Server.terminateOsp(process_id,terminateBtn)
        }

    }

    const progressOfMonitoringOrders = {
        render : function(process){
            popup.popupHeaderEl.innerText = "Progrès de suivi des commandes"        
            popup.popupBodyEl.innerHTML = `
                <div class="progress-of-monitoring-orders" >
                    <div class="spinner-border dz-spinner" role="status" >
                    <span class="sr-only">Loading...</span>
                    </div>
                    <p style="position:relative"><span class="dz-monitored-orders-cnt">0</span>/<span class="orders-to-monitor-cnt">${process.items_to_process_cnt}</span> commandes ont été suivies</span></p>
                </div>
                `
            popup.popupFooterEl.innerHTML = ''
            popup.open()
            Server.monitorOmp(process.id)
            
        }
    }
    
    create_the_start_btn()
    popup.create();
    eventPopup.create()
    popupOverlay.create();
    Server.monitorNotifications()


    buttons = [
        {
            'name' : 'Détails',
            'className' : "dz-event-popup-btn",
            'clickHandler' : function(){
                let process_detail_url = dz_module_controller_base_url+"/order_submit_process/"+process.id+"/?_token="+_token
                window.open(process_detail_url,"_blank")
                eventPopup.close();
            }
        }
    ]



    
})

