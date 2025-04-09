// const moduleControllerBaseUrl = "http://localhost/prestashop/dz_admin/dz" ;
const urlParams = new URLSearchParams(window.location.search);
const _token = urlParams.get('_token');

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
    addFooter :function(carrier_name){
        this.popupFooterEl.style.display = 'flex' ;
        this.popupFooterEl.innerHTML = `<button onclick="updateCarrier('${carrier_name}')">Modifier</button>`
    },
    open : function(carrier_name,carrier_logo){
        this.popupHeaderEl.innerHTML = "<p>Le transporteur "+carrier_name+"</p>"
        this.popupBodyEl.innerHTML = `
            <div class="spinner-border dz-spinner" role="status" >
                <span class="sr-only">Loading...</span>
            </div>
        `
        if(this.popupFooterEl){
            this.popupFooterEl.style.display = "none" ; 
        }
        this.loadCarrierDetail(carrier_name,carrier_logo)
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
    generateCarrierForm : function(carrier){
        carrierForm = ""
        if(carrier.api_credentials){
            if(carrier.api_credentials.is_user_id_required){
                carrierForm += `<div class="form-group">
                                    <label>Identifiant </label>
                                    <input class="form-control" name="dz__api_credentials__user_id" value="${carrier.api_credentials.user_id}" placeholder="Identifiant" onfocus="handleCarrierFormFieldChange(this)" />
                                </div>`
            }
            carrierForm += `
                <div class="form-group">
                    <label>Clé d'api</label>
                    <input type="password" class="form-control" name="dz__api_credentials__token" value="${carrier.api_credentials.token}" placeholder="Clé d'api" onfocus="handleCarrierFormFieldChange(this)" />
                    <div class="dz-checkbox-container">
                        <label >
                            <input type="checkbox"  />
                            <span onclick="togglePasswordFied('dz__api_credentials__token')" class="dz-checkmark"></span>
                        </label>
                        <small>afficher le clé d'api</small>
                    </div>
                </div>
            `
        }

        if(carrier.website_credentials){
            carrierForm +=`
                                <div class="form-group">
                                    <label>E-mail</label>
                                    <input class="form-control" name="dz__website_credentials__email" value="${carrier.website_credentials.email}" placeholder="E-mail" onfocus="handleCarrierFormFieldChange(this)" />
                                </div>
                                <div class="form-group">
                                    <label>Mot de passe</label>
                                    <input class="form-control" type="password" name="dz__website_credentials__password" value="${carrier.website_credentials.password}" placeholder="Mot de passe" onfocus="handleCarrierFormFieldChange(this)"  />
                                    <div class="dz-checkbox-container">
                                        <label>
                                            <input type="checkbox"  >
                                            <span class="dz-checkmark" onclick="togglePasswordFied('dz__website_credentials__password')"></span>
                                        </label>
                                        <small>afficher le mot de passe</small>
                                    </div>
                                </div>
                            `
        }
        return carrierForm ;
    },
    loadCarrierDetail : function(carrier_name,carrier_logo){
        fetch(dz_module_controller_base_url+"/carrier/"+carrier_name+"?_token="+_token, {
            method: 'GET',
            credentials: 'include', // Ensures cookies are sent with the request
            headers: {
                'Content-Type': 'application/json',
            },
        }).then(response=>response.json())
          .then(data=>{
                if(data.status == "success"){
                    let carrierForm = this.generateCarrierForm(data.carrier)
                    this.popupBodyEl.innerHTML = `<img src="${dz_module_media_base_url}/${carrier_logo}" />`+carrierForm 
                    this.popupHeaderEl.innerHTML += "<i class='material-icons dz-close-popup-icon'  onclick='popup.close()'>close</i>"
                    this.addFooter(carrier_name)
                }
            })
    }
}

function togglePasswordFied(field_name){
    let passwordField = document.querySelector(`input[name='${field_name}']`)
    if (passwordField.type == "password"){
        passwordField.type = "text"
    }else{
        passwordField.type = "password"
    }
}

function handleCarrierFormFieldChange(field){
    if(field.style.borderColor ==  "red"){
        field.style.borderColor =  "#bbcdd2" ;
        field.nextElementSibling.remove()
    }
}


function addFieldErrorMessageAndStyle(carrierFormInput,error_message){
        carrierFormInput.style.borderColor = "red" ;
        let errorMsg = document.createElement("small");
        errorMsg.style.color = "red";
        errorMsg.innerText = error_message
        carrierFormInput.insertAdjacentElement("afterend", errorMsg);
}

function validateCarrierForm(carrier_name){
    let isFormValid = true ;
    let formData = {"website_credentials":{},"api_credentials":{}} ;
    const regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    let carrierFormInputs = document.querySelectorAll('.dz-popup-body .form-control');

    console.log(carrierFormInputs)
    carrierFormInputs.forEach((carrierFormInput)=>{
        console.log("carrier value : "+carrierFormInput.value)
        if(carrierFormInput.style.borderColor != "red" ){
            if(carrierFormInput.value == ""){
                addFieldErrorMessageAndStyle(carrierFormInput,"Ce champ est obligatoire.")    
                isFormValid = false                    
            }else  if(carrierFormInput.name == "dz__website_credentials__email" && !regexEmail.test(carrierFormInput.value)){
                addFieldErrorMessageAndStyle(carrierFormInput,"Cet email n'est pas valide")
                isFormValid = false
            }
            let [prefix,credential_type,field] = carrierFormInput.name.split("__")
            formData[credential_type][field] = carrierFormInput.value
        }else{
            isFormValid = false
        }
    })
    return [isFormValid,formData] ;
}

function disableOrEnablePopup(action){
    let carrierFormInputs = document.querySelectorAll('.dz-popup-body .form-control');
    carrierFormInputs.forEach((carrierFormInput)=>{
        carrierFormInput.disabled = action == "disable" ? true : false ;
    })
    document.querySelector(".dz-popup-footer button").disabled =  action == "disable" ? true : false ;
    document.querySelector(".dz-close-popup-icon").style.pointerEvents = action == "disable" ? "none" : "auto" ;
}

function updateCarrier(carrier_name){

    let [isCarrierFormValid,carrierFormData] = validateCarrierForm()
    console.log(carrierFormData)
    if(isCarrierFormValid){
        
        let updateCarrierBtn = popup.popupFooterEl.firstElementChild ;
        updateCarrierBtn.innerHTML += `<div class="spinner-border dz-btn-spinner-white" role="status" >
            <span class="sr-only">Loading...</span>
        </div>
        `
        disableOrEnablePopup("disable")
        fetch(dz_module_controller_base_url+"/carrier/"+carrier_name+"?_token="+_token, {
            method: 'PUT',
            credentials: 'include', // Ensures cookies are sent with the request
            headers: {
                'Content-Type': 'application/json',
            },
            body : JSON.stringify(carrierFormData)
        }).then(response=>response.json())
          .then(data=>{
                updateCarrierBtn.lastElementChild.remove()
                disableOrEnablePopup("enable")
                popup.close()
           })
            .catch((err)=>{
                        updateCarrierBtn.lastElementChild.remove()
                        disableOrEnablePopup("enable")
            })

    }

}

function saveSettings(saveButton){
    saveButton.disabled = true
    const order_post_submit_state_select = $("select[name='order_post_submit_state'") 
    order_post_submit_state_select.prop('disabled',true)
    
    saveButton.innerHTML += `<div class="spinner-border dz-btn-spinner-white" role="status" >
        <span class="sr-only">Loading...</span>
     </div>
    `

    fetch(dz_module_controller_base_url+"/update_settings?_token="+_token, {
        method: 'PUT',
        credentials: 'include', // Ensures cookies are sent with the request
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({'order_post_submit_state_id':order_post_submit_state_select.val()})
    }).then(response=>response.json())
      .then(data=>{
        if(data.status == "success"){
            saveButton.innerHTML = "Enregistrer" ;
            saveButton.disabled = false 
            order_post_submit_state_select.prop('disabled',false)
        }
      }).catch((err)=>{
            saveButton.innerHTML = "Enregistrer" ;
            saveButton.disabled = false 
            order_post_submit_state_select.prop('disabled',false)
      })
}
popup.create()
popupOverlay.create()