document.addEventListener('DOMContentLoaded', function() {

    const moduleControllerBaseUrl = window.location.href.split('/index.php')[0]+"/dz";
    const moduleMediaBaseUrl = window.location.href.split('/dz_admin/index.php')[0]+"/modules/dolzay/uploads";
    const urlParams = new URLSearchParams(window.location.search);
    const _token = urlParams.get('_token');
    const dz_carriers = ["Afex"] ;
    let selectedCarrier = "" ;

    const eventPopupTypesData = {
        info : {icon:`<i class="material-icons" style="color:#101B82" >info</i>`,color:'#101B82'},
        restricted : {icon:`<i class='fas fa-minus-circle' style="color:#D81010" ></i>`,color:'#D81010'},
        error : {icon : `<i class="material-icons" style="color:#D81010" >error</i>`,color:'#D81010'},
        success : {icon:`<i class="fas fa-check-circle" style="color:#28C20F" ></i>`,color:'#28C20F'},
        expired : {icon:`<img src='${moduleMediaBaseUrl}/expired.png' />`,color:'#D81010'}
    }


    function create_the_order_submit_btn(){
        const order_table_header = document.querySelectorAll("#order_grid .col-sm .row .col-sm .row")[0];
        const order_submit_btn = document.createElement('button')
        order_submit_btn.id="dz-order-submit-btn" ;
        order_submit_btn.innerText = "Soumttre les commandes"
        order_table_header.appendChild(order_submit_btn)
        order_submit_btn.addEventListener('click', ()=>{
            selectCarrierStep.render()       
        });
    }

    const Server = {
        launchOsp : function(orderIds,carrier,continueBtn,cancelBtn){
            fetch(moduleControllerBaseUrl+"/order_submit_process?_token="+_token, {
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
                    let process  = data.process 
                    if (process.hasOwnProperty('meta_data')){
                        alreadySubmittedAndInvalidOrdersStep.render(process)
                    }else{
                        process['items_to_process_cnt'] = orderIds.length
                        progressOfSubmittingOrdersStep.render(process)
                    }
                }else if(data.status == 'conflict'){ // handle the case of an exsint osp running
                    let process  = data.process 
                    buttons = [
                        {
                            'name' : 'Détail',
                            'className' : "dz-process-detail-btn",
                            'clickHandler' : function(){
                                            console.log(`go to the detail page of the process with the id : ${process.id}`)
                            }
                        }
                    ]
                    popup.close()
                    eventPopup.open("restricted","Restreint"
                                    ,"Vous ne pouvez pas soumettre de commandes pour le moment, car un processus de soumission de commandes est déjà en cours",
                                    buttons)
                }else{
                    continueBtn.disabled = false 
                    cancelBtn.disabled = false 
                }

            })
            .catch(error => {
                continueBtn.disabled = false 
                cancelBtn.disabled = false 
                console.error('Error:', error)

            });
        },
        continueOsp : function(process_id,ordersToResubmitIds,continueBtn,cancelBtn){
            let url = moduleControllerBaseUrl+"/order_submit_process/"+process_id+"/continue?_token="+_token
            console.log("url : "+url)
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
                
                if(data.status == "success"){
                    let items_to_process_cnt = data.items_to_process_cnt
                    if (items_to_process_cnt == 0){
                        popup.close()
                        buttons = [
                            {
                                'name' : 'Ok',
                                'className' : "dz-process-detail-btn",
                                'clickHandler' : function(){
                                    eventPopup.close()
                                }
                            }
                        ]
                        let message = ""

                        message = `Ce processus a été annulé, car il n'a aucune commande valide à soumettre.`
                        
                        eventPopup.open("info","Information",message,buttons)
                    }else{
                        let process = {id:process_id,items_to_process_cnt:items_to_process_cnt}
                        progressOfSubmittingOrdersStep.render(process)
                    }
                }else if(data.status == "conflict"){
                    popup.close()
                    buttons = [
                        {
                            'name' : 'Détail',
                            'className' : "dz-process-detail-btn",
                            'clickHandler' : function(){
                                            console.log(`go to the detail page of the process with the id : ${data.process_id}`)
                            }
                        }
                    ]
                    let message = ""
                    if (data.process_status == "Annulé par l'utilisateur"){
                        message = "Ce processus a été annulé par un autre utilisateur."
                    }else{
                        message = `Ce processus a été poursuivi par un autre utilisateur, et son état actuel est : ${data.process_status}.`
                    }
                    eventPopup.open("info","Information",message,buttons)
                }else{
                    continueBtn.disabled = false
                    cancelBtn.disabled = false 
                }
            })
            .catch(error => {
                continueBtn.disabled = false
                cancelBtn.disabled = false 
                console.error('Error:', error)});
        },
        cancelOsp : function(process_id,cancelBtn,continueBtn){
            let url = moduleControllerBaseUrl+"/order_submit_process/"+process_id+"/cancel?_token="+_token
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
                    popup.close()
                    buttons = [
                        {
                            'name' : 'Détail',
                            'className' : "dz-process-detail-btn",
                            'clickHandler' : function(){
                                            console.log(`go to the detail page of the process with the id : ${process_id}`)
                            }
                        }
                    ]

                    let message = ""
                    if (data.process_status == "Annulé par l'utilisateur"){
                        message = "Ce processus a été annulé par un autre utilisateur."
                    }else{
                        message = `Ce processus a été poursuivi par un autre utilisateur, et son état actuel est : ${data.process_status}.`
                    }
                    eventPopup.open("info","Information",message,buttons)
                }else{
                    cancelBtn.disabled = false 
                    continueBtn.disabled = false 
                }

            })
            .catch(error =>{ 
                console.error('Error:', error)
                cancelBtn.disabled = false
                continueBtn.disabled = false 
            });
        },
        terminateOsp : function(process_id,terminateBtn){
            let url = moduleControllerBaseUrl+"/order_submit_process/"+process_id+"/terminate?_token="+_token
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
                terminateBtn.disabled = false
            })
            .catch(error =>{ 
                console.error('Error:', error)
                terminateBtn.disabled = false
            });
        },
        monitorOsp : function(process_id){
            let url = moduleControllerBaseUrl+"/order_submit_process/"+process_id+"/monitor?_token="+_token
            console.log("url : "+url)
            fetch(url, {
                method: 'GET',
                credentials: 'include' // Ensures cookies are sent with the request
            })
            .then(response => response.json())
            .then(function(data){
                console.log("monitoring data : ")
                console.log(data)
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
                                'name' : 'Ok',
                                'className' : "dz-process-detail-btn",
                                'clickHandler' : function(){
                                                eventPopup.close();
                                }
                            }
                        ]
    
                        let message = `${process.processed_items_cnt}/${process.items_to_process_cnt} commandes ont été soumises avec succès à ${process.carrier}.`
                        eventPopup.open("success","Succés",message,buttons)
                    }else if(process.status == "Terminé par utilisateur"){
                        popup.close()

                        buttons = [
                            {
                                'name' : 'Ok',
                                'className' : "dz-process-detail-btn",
                                'clickHandler' : function(){
                                                eventPopup.close();
                                }
                            }
                        ]

                        let message = "Le processus de soumission des commandes a bien été arrêté"
                        if(process.processed_items_cnt > 0){
                            message =`Le processus de soumission des commandes a bien été arrêté après la soumission de ${process.processed_items_cnt}/${process.items_to_process_cnt} commandes à ${process.carrier}.`
                        }
                        eventPopup.open("success","Succés",message,buttons)
                    }else if (process.status == "Interrompu"){
                        popup.close()
                        console.log(process)
                        buttons = [
                            {
                                'name' : 'Détail',
                                'className' : "dz-process-detail-btn",
                                'clickHandler' : function(){
                                    console.log(`go to the process with the id : ${process_id}`)
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
        popupOverlayEl : null,

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
    
            // create the carrier icon  
            const carrierIcon = document.createElement('img');
            carrierIcon.className = "dz-carrier-icon" 
            carrierIcon.src = moduleMediaBaseUrl+"/carrier_icon.png";
            popup.popupBodyEl.appendChild(carrierIcon);
    
            // create the carrier select 
            const select = document.createElement('select');
            select.className = "dz-carrier-select"
            for(let i=0 ;i<dz_carriers.length;i++){
                let option = document.createElement('option') ;
                option.value = dz_carriers[i] ;
                option.innerText = dz_carriers[i] ;
                if(i == 0){
                    option.selected = true ;
                }
                select.appendChild(option);
            }
            popup.popupBodyEl.appendChild(select);
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
            cancelBtn.disabled = false 
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
            popup.close();
        }
    }

    const alreadySubmittedAndInvalidOrdersStep = {

        render : function(process){
            popup.popupHeaderEl.innerText = "Choisir les commandes à re-soumettre et fixer les commandes invalides"
            popup.popupBodyEl.innerHTML = ''
            popup.popupFooterEl.innerHTML = ''
            popup.popupFooterEl.style.display = 'flex'

            // add the already submitted orders
            if(process.meta_data.hasOwnProperty('already_submitted_orders')){
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
    
                popup.popupBodyEl.innerHTML += `
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
            if(process.meta_data.hasOwnProperty('orders_with_invalid_fields')){
                let orders_with_invalid_fields = process.meta_data.orders_with_invalid_fields
                
                let table_rows = ""            
                for (let i = 0; i < orders_with_invalid_fields.length; i++) {
                    let order_link = this.getOrderLink(orders_with_invalid_fields[i].order_id)
                    
                    // get invalid fields 
                    let invalid_fields  = ""
                    for(let ii=0;ii<orders_with_invalid_fields[i].invalid_fields.length;ii++){
                        invalid_fields +=  `<span class='badge badge-secondary'>${orders_with_invalid_fields[i].invalid_fields[ii]}</span>`
                    }
                    
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
    
                popup.popupBodyEl.innerHTML += `
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
                let headInvalidOrderCheckbox = popup.popupBodyEl.querySelector(".dz-head-invalid-order-checkbox");
                headInvalidOrderCheckbox.addEventListener('click',function(e){alreadySubmittedAndInvalidOrdersStep.handleHeadCheckbox(e)})
            }

            // add click handlers for the checkboxes of submitted orders
            // note : i have to attach these click event listener after the content of popupBodyEl is fully set 
            // because the listeners added in the first .innerHTML+= will be deleted in the second .innerHTML+= because 
            // the nodes of the first dom update they will be recreated in the second dom update since .innerHTML value is just a string and not nodes  
            let headSubmittedOrderCheckbox = popup.popupBodyEl.querySelector(".dz-head-submitted-order-checkbox");
            if(headSubmittedOrderCheckbox){
                headSubmittedOrderCheckbox.addEventListener('click',function(e){alreadySubmittedAndInvalidOrdersStep.handleHeadCheckbox(e)})
            }

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
            let url = moduleControllerBaseUrl+"/order_submit_process/"+process_id+"/continue?_token="+_token
            console.log("url : "+url)
            Server.continueOsp(process_id,ordersToResubmitIds,continueBtn,cancelBtn)

        },
        cancel : function(cancelBtn,process_id){
            // disable the button then add to it a spinner
            cancelBtn.disabled=true
            let continueBtn = document.querySelector(".dz-continue-btn")
            cancelBtn.innerHTML += `              
                <div class="spinner-border dz-btn-spinner-blue" role="status" >
                    <span class="sr-only">Loading...</span>
                </div>
            `

            let url = moduleControllerBaseUrl+"/order_submit_process/"+process_id+"/cancel?_token="+_token
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
        render : function(process){
            popup.popupHeaderEl.innerText = "Progrès de la soumission des commandes"        
            popup.popupBodyEl.innerHTML = `
                <div class="spinner-border dz-spinner" role="status" >
                <span class="sr-only">Loading...</span>
                </div>
                <p style="position:relative"><span class="dz-submitted-orders-cnt">0</span>/<span class="orders-to-submit-cnt">${process.items_to_process_cnt}</span> commandes ont été soumises à <span class="dz-carrier">${selectedCarrier}</span></p>`
            popup.popupFooterEl.innerHTML = ''

            // add the cancel and the continue buttons 
            const buttons = [
                {
                    'name' : 'Terminer',
                    'className' : "dz-terminate-btn",
                    'clickHandler' : function (){progressOfSubmittingOrdersStep.terminate(this,process.id)}
                }
            ]

            popup.addButtons(buttons);

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

    create_the_order_submit_btn();
    popup.create();
    eventPopup.create()
    popupOverlay.create();
    
})

/*
TRASH CODE : 

        checkForARunningProcess : function(recursive){
            fetch(
                moduleControllerBaseUrl+"/order_submit_process/check_for_a_running_process?_token="+_token,{
                    method : 'GET',
                    credentials: 'include'
                }
            ).then(response => response.json())
            .then(function (data){
                if(data.status == 'success' && data.process ){
                    let process = data.process ;
                    if(process.status == "Initié"){
                        if(!recursive){
                            showTheLoaderOfCheckingAlreadySubmittedAnOrdersWithInvalidFields(false)
                        }
                        // re-check the status the osp after 2 seconds 
                        setTimeout(()=>{Server.checkForARunningProcess(true)},2000)
                    }else if(process.status == "Contient des commandes invalides"){
                        
                        alreadySubmittedAndInvalidOrdersStep.render(data.process)
                    }
                    else if (process.status == "Actif"){
                        progressOfSubmittingOrdersStep.render(process)
                    }
    
                    // handle the case of recursive=true and the process did end 
                    if(recursive && ospFinalStatuses.includes(process['status'])){
                        popup.close()
                        buttons = [
                            {
                                'name' : 'Détail',
                                'className' : "dz-process-detail-btn",
                                'clickHandler' : function(){
                                    console.log("go to the detail page of the process with the id : "+process['id'])
                                }
                            }
                        ]
                        let message = "Le processus s'est arrêté avec le statut :"+process['status']
                        eventPopup.open("information","Information",message,buttons)
                    }
                }
            })
        } 

*/
