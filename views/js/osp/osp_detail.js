function toggleProcessErrorDetail(){
    let detailAnchor = $(".dz-more-detail")
    let errorMsgDetail = $(".dz-error-message-detail")
    errorMsgDetail.toggle()
    if(errorMsgDetail.css('display') =="block"){
        detailAnchor.text("moins de détail")
    }else{
        detailAnchor.text("détail")
    }
}

const current_link = window.location.href ; 
const urlParams = new URLSearchParams(window.location.search);
const _token = urlParams.get('_token');
const status_colors = {
    "Initié": "#FFD700",
    "Actif": "green",
    "Pre-terminé par l'utilisateur": "orange",
    "Terminé par l'utilisateur": "gray",
    "Interrompu": "red",
    "Annulé par l'utilisateur": "gray",
    "Annulé automatiquement": "gray",
    "Terminé": "gray"
};

const process_active_statuses = [
    "Actif",
    "Initié"
]

const process_end_statuses = [
    "Terminé par l'utilisateur",
    "Interrompu",
    "Annulé par l'utilisateur",
    "Annulé automatiquement",
    "Terminé"
]

const eventPopupTypesData = {
    info : {icon:`<i class="material-icons" style="color:#101B82" >info</i>`,color:'#101B82'},
    restricted : {icon:`<i class='fas fa-minus-circle' style="color:#D81010" ></i>`,color:'#D81010'},
    error : {icon : `<i class="material-icons" style="color:#D81010" >error</i>`,color:'#D81010'},
    success : {icon:`<i class="fas fa-check-circle" style="color:#28C20F" ></i>`,color:'#28C20F'},
    error_detail : {icon : '',color:'#D81010'}
}

const eventPopupOverlay = {
    eventPopupOverlayEl : null,
    create : function(){
        this.eventPopupOverlayEl = document.createElement('div');
        this.eventPopupOverlayEl.className = "dz-event-popup-overlay";
        document.body.appendChild(this.eventPopupOverlayEl);
    },
    show : function(){
        this.eventPopupOverlayEl.classList.add('dz-show')
    },
    hide : function(){
        this.eventPopupOverlayEl.classList.remove('dz-show')
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
        
        buttons.forEach((button) => {
            const buttonEl = document.createElement('button');
            buttonEl.textContent = button.name ;
            buttonEl.style.backgroundColor = color ;
            buttonEl.addEventListener('click',button.clickHandler);
            this.popupFooterEl.appendChild(buttonEl);
        });
        
    },
    open : function(type,title,message,buttons) {
        setTimeout(() => {
            eventPopupOverlay.show()
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
            eventPopupOverlay.hide()
            this.popupFooterEl.innerHTML = "" ;
            this.popupEl.classList.remove('dz-show');
        }, 300);
    },
}


let processStatusEl  = $(".dz-process-status")
let process_id = current_link.split("dz/order_submit_process/")[1].split("/")[0]

// if the fixed footer exist 
if($(".dz-fixed-footer").length){
    // add margin to the bottom of the orders to submit container 
    $(".dz-order-list-container").css("margin-bottom","53px")
    // add a click event listener to the terminate btn 
    $('.dz-process-terminate-btn').on('click', terminateOrderSubmitProcess);
}


// monitor the process if it's active
let process_status = processStatusEl.text()
if(!process_end_statuses.includes(process_status)){
    setTimeout(monitorOrderSubmitProcess,3000)
}

function formatDatetime(datetime_str){
    const [year,month,day,hour,minute,second] = datetime_str.split(/[- :]/);
    return `${day}-${month}-${year} ${hour}:${minute}:${second}`
}

function terminateOrderSubmitProcess(){
    $('.dz-process-terminate-btn').css("disabled",true)
    $('.dz-process-terminate-btn').append(`              
        <div class="spinner-border dz-btn-spinner-white" role="status" >
            <span class="sr-only">Loading...</span>
        </div>
    `)
    let [first_part,second_part] = current_link.split("/?")
    let terminate_link = first_part+"/terminate?"+second_part
    fetch(terminate_link,{
        method : "PUT",
        credentials : "include"
    }).catch(err=>{
        $('.dz-process-terminate-btn').css("disabled",true)
        $(".dz-btn-spinner-white").remove()
    })
}

function monitorOrderSubmitProcess(){


    let query_parameters = {
        submitted_orders__order_id : $(`.dz-submitted-orders-container .dz-filter-form input[name='order_id']`).val(),
        submitted_orders__client : $(`.dz-submitted-orders-container .dz-filter-form input[name='client']`).val(),
        submitted_orders__page_nb : $(`.dz-submitted-orders-container .dz-page-nb-select`).val(),
        submitted_orders__batch_size : $(`.dz-submitted-orders-container .dz-batch-size-select`).val(),

        orders_with_errors__order_id : $(`.dz-orders-with-errors-container .dz-filter-form input[name='order_id']`).val(),
        orders_with_errors__client : $(`.dz-orders-with-errors-container .dz-filter-form input[name='client']`).val(),
        orders_with_errors__page_nb : $(`.dz-orders-with-errors-container .dz-page-nb-select`).val(),
        orders_with_errors__batch_size : $(`.dz-orders-with-errors-container .dz-batch-size-select`).val(),
       
        is_json : true
    }
    

    let params = new URLSearchParams(query_parameters);
    let order_submit_process_detail_link = `${current_link}&${params}`
    fetch(order_submit_process_detail_link,{
        method : "GET",
        credentials : "include"
    })
    .then(response => response.json())
    .then(data => {
        if (data.status == "success"){

            let order_submit_process = data.order_submit_process
            
            // update the status 
            processStatusEl.text(order_submit_process.status)
            processStatusEl.css("background-color",status_colors[order_submit_process.status])                

            // update the end date 
            if (order_submit_process.ended_at){
                $(".dz-process-end-date").text(formatDatetime(order_submit_process.ended_at))
            }
            
            // update the progress 
            if(order_submit_process.items_to_process_cnt){
                if($(".dz-progress-placeholder").length){
                    $(".dz-process-submitted-orders").text(order_submit_process.processed_items_cnt)
                    $(".dz-progress").css("width",`${order_submit_process.processed_items_cnt / order_submit_process.items_to_process_cnt * 100}%`)
                }else{
                    $(".dz-process-progress").html(
                        `
                            <label>Progrés : </label>
                            <div class="dz-progress-placeholder">
                                <div class="dz-progress" style="width:${order_submit_process.processed_items_cnt / order_submit_process.items_to_process_cnt * 100}%">
                                    <span class="dz-process-submitted-orders">${order_submit_process.processed_items_cnt}</span>
                                </div>
                                <span class="dz-process-orders-to-submit">${order_submit_process.items_to_process_cnt}</span>
                            </div>
                        `
                    )
                    $(".dz-process-progress").addClass("dz-vertical-direction")
                }
            }
            // update the error
            if(order_submit_process.error){
                if($(".dz-error-message-container").length == 0){
                        let processErrorDetail = ``
                        if(order_submit_process.error.detail){
                                processErrorDetail=`
                                    <span class="dz-more-detail" onClick="toggleProcessErrorDetail()">détail</span>
                                    <pre class="dz-error-message-detail">${JSON.stringify(order_submit_process.error.detail, null, 4)}</pre>
                                `
                        }

                        $(".dz-process-error").html(
                            `
                                <label>message d ‘erreur : </label> 
                                <div class="dz-error-message-container">
                                    <span class="dz-error-message">
                                        ${order_submit_process.error.message}
                                        ${processErrorDetail}
                                    </span>
                                </div>
                            `
                        )
                        $(".dz-process-error").addClass("dz-vertical-direction")
                }
            }
            
            // update the table and the pagination of the submitted orders 
            let submitted_orders = order_submit_process.submitted_orders
            let submitted_orders_total_count = submitted_orders.length ? submitted_orders[0].total_count : 0
            updateTable("dz-submitted-orders-container",submitted_orders);
            updatePagination("dz-submitted-orders-container",submitted_orders_total_count);

            let orders_with_errors = order_submit_process.orders_with_errors
            let orders_with_errors_total_count = orders_with_errors.length ? orders_with_errors[0].total_count : 0
            updateTable("dz-orders-with-errors-container",orders_with_errors);
            updatePagination("dz-orders-with-errors-container",orders_with_errors_total_count);


            // show event popups for final statuses
            if(order_submit_process.status == "Terminé" ){
                eventPopup.create()
                eventPopupOverlay.create();
                buttons = [
                    {
                        'name' : 'Ok',
                        'clickHandler' : function(){
                            eventPopup.close();
                        }
                    }
                ]

                let message = `${order_submit_process.processed_items_cnt}/${order_submit_process.items_to_process_cnt} commandes ont été soumises avec succès à ${order_submit_process.carrier}.`
                eventPopup.open("success","Succés",message,buttons)

                // hide the the fixed footer
                $(".dz-fixed-footer").hide()
                $(".dz-order-list-container").css("margin-bottom","0px")
            }else if(order_submit_process.status == "Terminé par l'utilisateur"){
                eventPopup.create()
                eventPopupOverlay.create();
                buttons = [
                    {
                        'name' : 'Ok',
                        'clickHandler' : function(){
                                        eventPopup.close();
                        }
                    }
                ]

                let message = "Le processus de soumission des commandes a bien été arrêté"
                if(order_submit_process.processed_items_cnt > 0){
                    message =`Le processus de soumission des commandes a bien été arrêté après la soumission de ${order_submit_process.processed_items_cnt}/${order_submit_process.items_to_process_cnt} commandes à ${order_submit_process.carrier}.`
                }
                eventPopup.open("success","Succés",message,buttons)
                
                // hide the the fixed footer
                $(".dz-fixed-footer").hide()
                $(".dz-order-list-container").css("margin-bottom","0px")
            }else if (order_submit_process.status == "Interrompu"){
                eventPopup.create();
                eventPopupOverlay.create();
                buttons = [
                    {
                        'name' : 'Ok',
                        'clickHandler' : function(){
                            eventPopup.close();
                        }
                    }
                ]
                eventPopup.open("error","Erreur",order_submit_process.error.message,buttons)

                // hide the the fixed footer
                $(".dz-fixed-footer").hide()
                $(".dz-order-list-container").css("margin-bottom","0px")
            }else{
                // since the process is still active monitor the process again within 2 seconds
                setTimeout(monitorOrderSubmitProcess,2000);
            }
        }   
    })
    .catch(error =>{
        console.log('Error:', error)
        setTimeout(monitorOrderSubmitProcess,2000);
    });
}

function goToOrder(orderId){
    window.location = current_link.replace("/dz/order_submit_process/"+process_id+"/","/sell/orders/"+orderId+"/view")
}

function openErrorDetailEventPopup(error_detail){
    error_detail = JSON.parse(error_detail)
    console.log(error_detail)
    eventPopup.create()
    eventPopupOverlay.create();
    buttons = [
        {
            'name' : 'Ok',
            'clickHandler' : function(){
                            eventPopup.close();
            }
        }
    ]

    error_detail = "<pre class='dz-error-message-container'>" + JSON.stringify(error_detail, null, 2) + "</pre>"
    console.log(error_detail)
    eventPopup.open("error_detail","Détail d'erreur",error_detail,buttons)
}

function updateTheOrderList(container,trigger) {
    // show the loading spinner 
    $(`.${container} .dz-loading-overlay`).css('display', 'flex')

    // disable all of the selects and input in the process list container 
    $(`.${container} .dz-filter-form select,.${container} .dz-filter-form input`).prop('disabled',true)

    if (trigger != "page_nb"){
        $(`.${container} .dz-page-nb-select`).val(1)
    }

    // make the request 
    let query_parameters = {
        order_id: $(`.${container} .dz-filter-form input[name='order_id']`).val(),
        client : $(`.${container} .dz-filter-form input[name='client']`).val(),
        page_nb: $(`.${container} .dz-page-nb-select`).val(),
        batch_size: $(`.${container} .dz-batch-size-select`).val()
    }

    if(container == "dz-orders-with-errors-container"){
        query_parameters['error_type'] = $(`.${container} .dz-filter-form select[name='error_type']`).val()
    }

    console.log(query_parameters)
    let params = new URLSearchParams(query_parameters);
    let osp_orders_endpoint = ""
    if (container == "dz-submitted-orders-container"){
        osp_orders_endpoint = "/dz/order_submit_process/"+process_id+"/submitted_orders/"
    }else{
        osp_orders_endpoint = "/dz/order_submit_process/"+process_id+"/orders_with_errors/"
    }
    let osp_orders_link = current_link.replace("/dz/order_submit_process/"+process_id+"/",osp_orders_endpoint)
    osp_orders_link = `${osp_orders_link}&${params}`
    fetch(osp_orders_link,{
        method : "GET",
        credentials : "include"
    })
        .then(response => response.json())
        .then(data => {
            if (data.status == "success"){
                //updateTable(data.order_submit_processes);
                let orders  = data.orders
                let total_count = orders.length ? orders[0].total_count : 0
                updateTable(container,orders);
                updatePagination(container,total_count);
                // show the loading spinner
                $(`.${container} .dz-loading-overlay`).hide()
                // disable all of the selects and input in the process list container 
                $(`.${container} .dz-filter-form select,.${container} .dz-filter-form input`).prop('disabled',false)
            }   
        })
        .catch(error =>{console.error('Error:', error)
                // hide the loading spinner 
                $(`.${container} .dz-loading-overlay`).css('display', 'none')
                // re-enable all of the selects and input in the process list container 
                $(`.${container} .dz-filter-form select,.${container} .dz-filter-form input`).prop('disabled',false)
        });
}

function updateTable(container,orders) {
    const tbody = $(`.${container} .dz-order-list-table-body`);
    tbody.empty();
    
    orders.forEach(order => {
        // id,carrier,started_at,processed_items_cnt,items_to_process_cnt,status
        let table_row = `
            <tr>
                <td>${order.id_order}</td>
                <td>${order.firstname} ${order.lastname}</td>
            `

        if(container == "dz-orders-with-errors-container"){
            table_row += `<td><button class="dz-order-error-type-btn" onClick='openErrorDetailEventPopup(${JSON.stringify(order.error_detail).replace(/'/g, "\\'")})'>${order.error_type}</button></td>`;
        }

        table_row += `
                <td><span class="dz-order-detail-link" onClick="goToOrder(${order.id_order})" ><i class="material-icons">remove_red_eye</i></span></td>
            </tr>`
        tbody.append(table_row)
    });
}

function updatePagination(container,totalCount) {
    let page_nb_select = $(`.${container} .dz-page-nb-select`)
    if (totalCount == 0){
        $(`.${container} .dz-pagination-range`).text("0 to 0 / 0")
        page_nb_select.empty()
        page_nb_select.append(new Option("1", 1));
        
    }else{
        let batch_size_val = $(`.${container} .dz-batch-size-select`).val()
        
        // update the options of the page_nb select based on the total count
        let page_nb_val = page_nb_select.val()
        total_pages = Math.ceil(totalCount / batch_size_val)

        // if the selected page doesn't exist anly more reset to the first page
        if (page_nb_val > total_pages ){
            page_nb_val = 1 
        } 

        page_nb_select.empty()
        for (let i = 1; i <= total_pages; i++) {
            page_nb_select.append(new Option(`${i}`, i));
        }
        
        // update the pagination_range :
        let firstEnd =  batch_size_val * (page_nb_val - 1) + 1
        let lastEnd = batch_size_val * page_nb_val
        
        // if we have only one page or the selected page doesn't exists any more
        if(total_pages == 1){
            page_nb_val = 1
            firstEnd = 1
            lastEnd = totalCount
        }// if we are in the last page
        else if(total_pages == page_nb_val){ 
            lastEnd = totalCount
        }

        // the set the value of the page nb and the pagination range
        page_nb_select.val(page_nb_val)
        $(`.${container} .dz-pagination-range`).text(`${firstEnd} to ${lastEnd} / ${totalCount}`)
    }
}

// setup the event listeners for the submitted orders container
$('.dz-submitted-orders-container .dz-filter-btn').on('click', function(e) {
    updateTheOrderList("dz-submitted-orders-container","filter");
});

$('.dz-submitted-orders-container .dz-batch-size-select').on('change', function() {
    updateTheOrderList("dz-submitted-orders-container","batch_size");
});

$('.dz-submitted-orders-container .dz-page-nb-select').on('change', function() {
    updateTheOrderList("dz-submitted-orders-container","page_nb");
});

// setup the event listeners for the orders with errors container
$('.dz-orders-with-errors-container .dz-filter-btn').on('click', function(e) {
    updateTheOrderList("dz-orders-with-errors-container","filter");
});

$('.dz-orders-with-errors-container .dz-batch-size-select').on('change', function() {
    updateTheOrderList("dz-orders-with-errors-container","batch_size");
});

$('.dz-orders-with-errors-container .dz-page-nb-select').on('change', function() {
    updateTheOrderList("dz-orders-with-errors-container","page_nb");
});