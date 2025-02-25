
document.addEventListener('DOMContentLoaded', function(){ 

const moduleMediaBaseUrl = window.location.href.split('/dz_admin/index.php')[0]+"/modules/dolzay/uploads";
const eventPopupTypesData = {
    expired : {icon:`<img src='${moduleMediaBaseUrl}/expired.png' />`,color:'#D81010'}
}


function create_the_order_submit_btn(){

    const bottom_bar = document.createElement("div")
    bottom_bar.className = "dz-bottom-bar"

    const order_submit_btn = document.createElement('button')
    order_submit_btn.id="dz-order-submit-btn" ;
    order_submit_btn.innerText = "Soumettre les commandes"

    order_submit_btn.addEventListener('click', ()=>{
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
    });

    document.querySelector("#order_grid_panel").style.marginBottom = "60px"
    bottom_bar.appendChild(order_submit_btn)
    document.body.appendChild(bottom_bar)
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

create_the_order_submit_btn();
popupOverlay.create();
eventPopup.create();
})