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
let processListOverlay = $(".dz-loading-overlay")
let start_date = null 
let end_date = null 

function goToProcess(processId){
    window.location = window.location.href.replace("order_submit_processes","order_submit_process/"+processId+"/")
}

function updateTheProcessList(trigger) {
    // show the loading spinner 
    processListOverlay.css('display', 'flex')
    // disable all of the selects and input in the process list container 
    $(".dz-process-list-container select,.dz-process-list-container input").prop('disabled',true)

    if (trigger != "page_nb"){
        $('.dz-page-nb-select').val(1)
    }

    // make the request 

    let query_parameters = {
        carrier: $('.dz-carrier-select').val(),
        status: $('.dz-status-select').val(),
        start_date: start_date,
        end_date: end_date,
        page_nb: $('.dz-page-nb-select').val(),
        batch_size: $(".dz-batch-size-select").val(),
        is_json : true
    }
    console.log(query_parameters)
    const params = new URLSearchParams(query_parameters);

    const current_link = window.location.href ; 
    let order_submit_process_list_link = `${current_link}&${params}`
    fetch(order_submit_process_list_link,{
        method : "GET",
        credentials: "include"
    })
        .then(response => response.json())
        .then(data => {
            if (data.status == "success"){
                //updateTable(data.order_submit_processes);
                let order_submit_processes = data.order_submit_processes
                let total_count = order_submit_processes.length ? order_submit_processes[0].total_count : 0
                updateTable(order_submit_processes);
                updatePagination(total_count);
                // show the loading spinner
                processListOverlay.hide()
                // disable all of the selects and input in the process list container 
                $(".dz-process-list-container select,.dz-process-list-container input").prop('disabled',false)
            }   
        })
        .catch(error =>{console.error('Error:', error)
                // hide the loading spinner 
                processListOverlay.css('display', 'none')
                // re-enable all of the selects and input in the process list container 
                $(".dz-process-list-container select,.dz-process-list-container input").prop('disabled',false)
        });
}

function updateTable(processes) {
    const tbody = $(".dz-process-list-table-body");
    tbody.empty();
    
    processes.forEach(process => {
        // id,carrier,started_at,processed_items_cnt,items_to_process_cnt,status
        let row = `
            <tr>
                <td>${process.id}</td>
                <td>${process.started_at}</td>`
        if(!process.items_to_process_cnt){
            row += `<td>___</td>`
        }else{
            row += `<td>${process.processed_items_cnt}/${process.items_to_process_cnt}</td>`
        }
        row +=`
                <td>${process.carrier}</td>
                <td><span class="dz-badge" style="background-color:${status_colors[process.status]}">${process.status}</span></td>
                <td><span class="dz-process-detail-link" onClick="goToProcess(${process.id})" ><i class="material-icons">remove_red_eye</i></span></td>
            </tr>`
        tbody.append(row)
    });
}

function updatePagination(totalCount) {
    let page_nb_select = $(".dz-page-nb-select")
    if (totalCount == 0){
        $(".dz-pagination-range").text("0 to 0 / 0")
        page_nb_select.empty()
        page_nb_select.append(new Option("1", 1));
        
    }else{
        let batch_size_val = $(".dz-batch-size-select").val()
        
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

        page_nb_select.val(page_nb_val)
        $(".dz-pagination-range").text(`${firstEnd} to ${lastEnd} / ${totalCount}`)
    }
}


$(".dz-date-range").daterangepicker({
    opens: 'left',
    autoUpdateInput: false,
    locale: {
        cancelLabel: 'Clear'
    }
}, function(start, end, label) {
    console.log("A new date selection was made: " + start.format('DD-MM-YYYY') + ' to ' + end.format('YYYY-MM-DD'));
    $('.dz-date-range').val("du : "+start.format('DD-MM-YYYY')+" au : "+end.format('DD-MM-YYYY'));
    start_date = start.format('YYYY-MM-DD')
    end_date = end.format('YYYY-MM-DD')
});

$('.dz-date-range').on('cancel.daterangepicker', function(ev, picker) {
    $('.dz-date-range').val('');
    start_date = null
    end_date = null
});


$('.dz-filter-btn').on('click', function(e) {
    updateTheProcessList("filter");
});

$('.dz-batch-size-select').on('change', function() {
    updateTheProcessList("batch_size");
});

$('.dz-page-nb-select').on('change', function() {
    updateTheProcessList("page_nb");
});