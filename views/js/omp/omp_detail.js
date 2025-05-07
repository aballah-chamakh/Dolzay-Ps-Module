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

let orderListOverlay = $(".dz-loading-overlay")
let processStatusEl  = $(".dz-process-status")
let process_id = current_link.split("dz/order_monitoring_process/")[1].split("/")[0]



// monitor the process if it's active
let process_status = processStatusEl.text()
if(!process_end_statuses.includes(process_status)){
    setTimeout(monitorOrderMonitoringProcess,3000)
}

function formatDatetime(datetime_str){
    const [year,month,day,hour,minute,second] = datetime_str.split(/[- :]/);
    return `${day}-${month}-${year} ${hour}:${minute}:${second}`
}



function monitorOrderMonitoringProcess(){

    let query_parameters = {
        updated_orders__order_id : $(`.dz-updated-orders-container .dz-filter-form input[name='order_id']`).val(),
        updated_orders__client : $(`.dz-updated-orders-container .dz-filter-form input[name='client']`).val(),
        old_status : $(`.dz-updated-orders-container .dz-filter-form select[name='old_status']`).val(),
        new_status : $(`.dz-updated-orders-container .dz-filter-form select[name='new_status']`).val(),
        updated_orders__page_nb : $(`.dz-updated-orders-container .dz-page-nb-select`).val(),
        updated_orders__batch_size : $(`.dz-updated-orders-container .dz-batch-size-select`).val(),

        orders_with_errors__order_id : $(`.dz-orders-with-errors-container .dz-filter-form input[name='order_id']`).val(),
        orders_with_errors__client : $(`.dz-orders-with-errors-container .dz-filter-form input[name='client']`).val(),
        orders_with_errors__page_nb : $(`.dz-orders-with-errors-container .dz-page-nb-select`).val(),
        orders_with_errors__batch_size : $(`.dz-orders-with-errors-container .dz-batch-size-select`).val(),
       
        is_json : true
    }
    

    let params = new URLSearchParams(query_parameters);
    let order_monitoring_process_detail_link = `${current_link}&${params}`
    fetch(order_monitoring_process_detail_link,{
        method : "GET",
        credentials : "include"
    })
    .then(response => response.json())
    .then(data => {
        if (data.status == "success"){

            let order_monitoring_process = data.order_monitoring_process
            
            // update the status 
            processStatusEl.text(order_monitoring_process.status)
            processStatusEl.css("background-color",status_colors[order_monitoring_process.status])                

            // update the end date 
            if (order_monitoring_process.ended_at){
                $(".dz-process-end-date").text(formatDatetime(order_monitoring_process.ended_at))
            }
            
            // update the progress 
            if(order_monitoring_process.items_to_process_cnt){
                if($(".dz-progress-placeholder").length){
                    $(".dz-process-monitored-orders").text(order_monitoring_process.processed_items_cnt)
                    $(".dz-progress").css("width",`${order_monitoring_process.processed_items_cnt / order_monitoring_process.items_to_process_cnt * 100}%`)
                }else{
                    $(".dz-process-progress").html(
                        `
                            <label>Progrés : </label>
                            <div class="dz-progress-placeholder">
                                <div class="dz-progress" style="width:${order_monitoring_process.processed_items_cnt / order_monitoring_process.items_to_process_cnt * 100}%">
                                    <span class="dz-process-monitored-orders">${order_monitoring_process.processed_items_cnt}</span>
                                </div>
                                <span class="dz-process-orders-to-monitor">${order_monitoring_process.items_to_process_cnt}</span>
                            </div>
                        `
                    )
                    $(".dz-process-progress").addClass("dz-vertical-direction")
                }
            }
            // update the error
            if(order_monitoring_process.error){
                if($(".dz-error-message-container").length == 0){
                        let processErrorDetail = ``
                        if(order_monitoring_process.error.detail){
                                processErrorDetail=`
                                    <span class="dz-more-detail" onClick="toggleProcessErrorDetail()">détail</span>
                                    <pre class="dz-error-message-detail">${JSON.stringify(order_monitoring_process.error.detail, null, 4)}</pre>
                                `
                        }

                        $(".dz-process-error").html(
                            `
                                <label>message d‘erreur : </label> 
                                <div class="dz-error-message-container">
                                    <span class="dz-error-message">
                                        ${order_monitoring_process.error.message}
                                        ${processErrorDetail}
                                    </span>
                                </div>
                            `
                        )
                        $(".dz-process-error").addClass("dz-vertical-direction")
                }
            }
            
            // update the kpis 
            //updateKpis(order_monitoring_process.kpis)


            // update the table and the pagination of the updated orders 
            let updated_orders = order_submit_process.updated_orders
            let updated_orders_total_count = updated_orders.length ? updated_orders[0].total_count : 0
            updateTable("dz-updated-orders-container",updated_orders);
            updatePagination("dz-updated-orders-container",updated_orders_total_count);

            // update the table and the pagination of the orders with errors 
            let orders_with_errors = order_submit_process.orders_with_errors
            let orders_with_errors_total_count = orders_with_errors.length ? orders_with_errors[0].total_count : 0
            updateTable("dz-orders-with-errors-container",orders_with_errors);
            updatePagination("dz-orders-with-errors-container",orders_with_errors_total_count);
            
            // show event popups for final statuses
            if(order_monitoring_process.status == "Terminé" ){
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

                let message = `${order_monitoring_process.processed_items_cnt}/${order_monitoring_process.items_to_process_cnt} commandes ont été suivies avec succès .`
                eventPopup.open("success","Succés",message,buttons)

                // hide the the fixed footer
                $(".dz-fixed-footer").hide()
                $(".dz-updated-orders-container").css("margin-bottom","0px")
            }else if (order_monitoring_process.status == "Interrompu"){
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
                eventPopup.open("error","Erreur",order_monitoring_process.error.message,buttons)

                // hide the the fixed footer
                $(".dz-fixed-footer").hide()
                $(".dz-updated-orders-container").css("margin-bottom","0px")
            }else{
                // since the process is still active monitor the process again within 2 seconds
                setTimeout(monitorOrderMonitoringProcess,2000);
            }
        }   
    })
    .catch(error =>{
        console.log('Error:', error)
        setTimeout(monitorOrderMonitoringProcess,2000);
    });
}

function goToOrder(orderId){
    window.location = current_link.replace("/dz/order_monitoring_process/"+process_id+"/","/sell/orders/"+orderId+"/view")
}

function updateTheOrderList(trigger) {
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
        batch_size: $(`.${container} .dz-batch-size-select`).val(),
        is_json : true
    }

    if(container == "dz-orders-with-errors-container"){
        query_parameters['error_type'] = $(`.${container} .dz-filter-form select[name='error_type']`).val()
    }else{
        query_parameters['new_status'] = $(`.${container} .dz-filter-form select[name='new_status']`).val()
        query_parameters['old_status'] = $(`.${container} .dz-filter-form select[name='old_status']`).val()
    }

    console.log(query_parameters)
    let params = new URLSearchParams(query_parameters);
    let omp_orders_endpoint = ""
    if (container == "dz-orders-with-errors-container"){
        omp_orders_endpoint = "/dz/order_monitoring_process/"+process_id+"/orders_with_errors/"
    }else{
        omp_orders_endpoint = "/dz/order_monitoring_process/"+process_id+"/updated_orders/"
    }
    let omp_orders_link = current_link.replace("/dz/order_monitoring_process/"+process_id+"/",omp_orders_endpoint)
    omp_orders_link = `${omp_orders_link}&${params}`
    fetch(omp_orders_link,{
        method : "GET",
        credentials : "include"
    })
        .then(response => response.json())
        .then(data => {
            if (data.status == "success"){
                //updateTable(data.order_monitoring_processes);
                let orders = data.orders
                let total_count = orders.length ? orders[0].total_count : 0
                //updateKpis(order_monitoring_process.kpis)
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

/*
function updateKpis(kpis){  
    const slider = $("#dz-kpi-slider");
    //console.log()
    slider.empty();  // Clears the content
    kpis.forEach(kpi=>{
    let kpi_card = `
        <div class="dz-kpi-card" style="background-color:${kpi.new_status_color}">
            <div class="dz-kpi-title">${kpi.new_status}</div>
            <div class="dz-kpi-value">${kpi.count}</div>
        </div>
    ` 
    slider.append(kpi_card)
   })

}
*/

function updateTable(container,orders) {
    const tbody = $(`.${container} .dz-order-list-table-body`);
    tbody.empty();
    
    orders.forEach(order => {
        // id,carrier,started_at,processed_items_cnt,items_to_process_cnt,status
        let table_row = `
            <tr>
                <td>${order.order_id}</td>
                <td>${order.firstname} ${order.lastname}</td>
            `
        if (container == "dz-orders-with-errors-container"){
            table_row += `
                <td><button class="dz-order-error-type-btn" onClick='openErrorDetailEventPopup(${JSON.stringify(order.error_detail).replace(/'/g, "\\'")})'${order.error_type}</button></td>
            `
        }else{
            table_row += `
                <td><span class="dz-badge" style="background-color:${order.old_status_color}">${order.old_status}</span></td>
                <td><span class="dz-badge" style="background-color:${order.new_status_color}">${order.new_status}</span></td>
            `
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

// setup the event listeners for the updated orders container

$('.dz-updated-orders-container .dz-filter-btn').on('click', function(e) {
    updateTheOrderList("dz-updated-orders-container","filter");
});

$('.dz-updated-orders-container .dz-batch-size-select').on('change', function() {
    updateTheOrderList("dz-updated-orders-container","batch_size");
});

$('.dz-updated-orders-container .dz-page-nb-select').on('change', function() {
    updateTheOrderList("dz-updated-orders-container","page_nb");
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

/* STARTING THE JS OF THE SLIDER
const cardWidth = 400 
const slider  = document.getElementById("dz-kpi-slider")
const prevBtn = document.getElementById('dz-prev-btn');
const nextBtn = document.getElementById('dz-next-btn');
let currentsliderPosition = 0 ;
    //    hidden part of he slider      |      visible part of the slider      |    hidden part of the slider 
    //         in the left                                                             in the right
    // -------------------------------- | ------------------------------------ | -------------------------------
    //      x is negative here          |           x is postive  here         |   x >= the offset width of the slider
    // cardRect.x + cardRect.width <= 0 |                                      |  cardRect.x >= slider.offsetWidth
    
function isTheCardVisibleInTheSlider(card) {
    const cardRect = card.getBoundingClientRect();
    if (cardRect.x >= 0 && cardRect.x + cardRect.width <= slider.offsetWidth) {
        return true;
    }
    return false 
}

function updateControlBtns(){
    // show or hide the control buttons based on the tolal number of cards and the width of slider container
    let kpiCards = document.querySelectorAll(".dz-kpi-card")

    if(slider.scrollWidth > slider.offsetWidth){
        // enable the control buttons 
        prevBtn.style.display = 'flex';
        nextBtn.style.display = 'flex';
    }else{
        // disable the control buttons 
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
    }

    /* disable the prev control button if we are in the first end and re-enable it if we aren't 
    if(slider.style.transform == "translateX(0px)"){
        prevBtn.disabled = true
    }else{
        prevBtn.disabled = false
    }

    // disable the prev control button if we are in the first end and re-enable it if we aren't 
    if(isTheCardVisibleInTheSlider(kpiCards[0])){
        console.log("the fiiiiiiiiiiirst card is visible")
        prevBtn.disabled = true
    }else{
        console.log("the fiiiiiiiiiiirst card is nooooooot visible")
        prevBtn.disabled = false
    }

    // disable the next control button if we are in the last end and re-enable it if we aren't 
    if(isTheCardVisibleInTheSlider(kpiCards[kpiCards.length-1])){
        console.log("the last card is visible")
        nextBtn.disabled = true
    }else{
        console.log("the last card is noooooooooot visible")
        nextBtn.disabled = false
    }

}


window.addEventListener('resize', function() {
    
    if(slider.offsetWidth < cardWidth){
        document.querySelectorAll(".dz-kpi-card").forEach((card)=>{
            card.style.width = "100%"
        })
    }else{
        document.querySelectorAll(".dz-kpi-card").forEach((card)=>{
            card.style.width = cardWidth +"px"
        })
    }

    updateControlBtns()
});

// Slide to the left
nextBtn.addEventListener('click', function() {

    // get the count of the card that can fit in the visible part of the container
    let cardsCountPerView = Math.trunc((slider.offsetWidth / (cardWidth + 10)))
    // get the distance we should scroll which is the width of next cards we can fit in the visible part of the container
    let distanceToScroll = (cardsCountPerView * (cardWidth + 10))
    // get the distance left to scroll 
    let distanceLeftToScroll = (slider.scrollWidth - (currentsliderPosition + slider.offsetWidth))
    console.log("distanceLeftToScroll : "+distanceLeftToScroll)
    console.log("distanceToScroll : "+distanceToScroll)
    if (distanceLeftToScroll >= distanceToScroll){
        currentsliderPosition += distanceToScroll
    }else{
        currentsliderPosition += distanceLeftToScroll
    }
    
    slider.style.transform = `translateX(-${currentsliderPosition}px)`;

});

// Slide to the right
prevBtn.addEventListener('click', function() {
    // get the count of the card that can fit in the visible part of the container
    let cardsCountPerView = Math.trunc((slider.offsetWidth / (cardWidth + 10)))
    // get the distance we should scroll which is the width of previoys cards we can fit in the visible part of the container
    let distanceToScroll = (cardsCountPerView * (cardWidth + 10))
    // the distanceLeftToScroll is the same as currentsliderPosition
    let distanceLeftToScroll = currentsliderPosition
    
    if (distanceLeftToScroll >= distanceToScroll){
        currentsliderPosition -= distanceToScroll
    }else{
        currentsliderPosition -= distanceLeftToScroll
    }
    
    slider.style.transform = `translateX(-${currentsliderPosition}px)`;
});

function onTransitionEnd(e) {
    if (e.propertyName === 'transform') {
        updateControlBtns();
    }
}

slider.addEventListener('transitionend', updateControlBtns);
// ENDING THE JS OF THE SLIDER
/*
const slider = document.getElementById('dz-kpi-slider');
const prevBtn = document.getElementById('dz-prev-btn');
const nextBtn = document.getElementById('dz-next-btn');
const sliderContainer = document.querySelector('.dz-slider-container');

let position = 0;
let cardCount = document.querySelectorAll('.dz-kpi-card').length;
let cardsPerView = 3; // Default for desktop

// Function to update the number of cards per view based on screen width
function updateCardsPerView() {
    const containerWidth = sliderContainer.offsetWidth;
    
    if (containerWidth < 768) {
        cardsPerView = 1;
        slider.classList.add('dz-single-card-mode');
        slider.classList.remove('dz-slider-mode');
    } else if (containerWidth < 992) {
        cardsPerView = 2;
        slider.classList.add('dz-slider-mode');
        slider.classList.remove('dz-single-card-mode');
    } else {
        cardsPerView = 3;
        slider.classList.add('dz-slider-mode');
        slider.classList.remove('dz-single-card-mode');
    }
    
    // If all cards fit, hide navigation
    if (cardCount <= cardsPerView) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'flex';
        nextBtn.style.display = 'flex';
    }
    
    return cardsPerView;
}

// Calculate dimensions and positions
function calculateDimensions() {
    cardsPerView = updateCardsPerView();
    
    const containerWidth = sliderContainer.offsetWidth;
    const cardWidth = (containerWidth - ((cardsPerView - 1) * 20)) / cardsPerView;
    const sliderWidth = (cardWidth * cardCount) + ((cardCount - 1) * 20);
    const maxPosition = Math.max(0, sliderWidth - containerWidth);
    
    // Reset position if it's out of bounds after resize
    position = Math.min(position, maxPosition);
    slider.style.transform = `translateX(-${position}px)`;
    
    return { cardWidth, sliderWidth, containerWidth, maxPosition };
}

// Update button states
function updateButtons() {
    const { maxPosition } = calculateDimensions();
    prevBtn.disabled = position <= 0;
    nextBtn.disabled = position >= maxPosition || maxPosition === 0;
}

// Slide to the left
prevBtn.addEventListener('click', function() {
    const { containerWidth, maxPosition } = calculateDimensions();
    const scrollAmount = Math.min(containerWidth, position);
    
    position = Math.max(0, position - scrollAmount);
    slider.style.transform = `translateX(-${position}px)`;
    updateButtons();
});

// Slide to the right
nextBtn.addEventListener('click', function() {
    const { containerWidth, maxPosition } = calculateDimensions();
    const scrollAmount = Math.min(containerWidth, maxPosition - position);
    
    position = Math.min(maxPosition, position + scrollAmount);
    slider.style.transform = `translateX(-${position}px)`;
    updateButtons();
});

// Handle window resize
window.addEventListener('resize', function() {
    calculateDimensions();
    updateButtons();
});

// Initialize
calculateDimensions();
updateButtons();
*/

