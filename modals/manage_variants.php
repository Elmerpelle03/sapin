<?php /* Manage Variants Modal (Option A: size multiplier) */ ?>
<div class="modal fade" id="manageVariantsModal" tabindex="-1" aria-labelledby="manageVariantsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="manageVariantsLabel">Manage Sizes & Prices</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <style>
          /* Responsive controls in variants table */
          #variantsTable td .mv-flex {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
          }
          #variantsTable td .mv-stock-input {
            flex: 1 1 160px; /* grow and show full digits */
            min-width: 140px;
          }
          #variantsTable td .btn-group .btn { white-space: nowrap; }
          @media (max-width: 576px){
            #variantsTable td .mv-stock-input { flex-basis: 100%; }
          }
        </style>
        <input type="hidden" id="mv-product-id" />
        <div class="table-responsive">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="small text-muted">Tip: Use quick buttons to add stock; click Save to apply and deduct materials.</div>
            <div class="d-flex align-items-center gap-2">
              <input type="number" id="mv-bulk-inc" class="form-control form-control-sm" style="width:120px" placeholder="+ amount" min="1" value="10">
              <button class="btn btn-sm btn-success" id="mv-apply-bulk"><i class="bi bi-plus-lg me-1"></i>Apply to all active</button>
            </div>
          </div>
          <table class="table table-sm align-middle" id="variantsTable">
            <thead>
              <tr>
                <th style="width:22%">Size</th>
                <th style="width:18%">Price (₱)</th>
                <th style="width:22%">Stock</th>
                <th style="width:22%">Size Multiplier</th>
                <th style="width:10%">Active</th>
                <th style="width:10%"></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <button class="btn btn-outline-primary" id="addVariantRowBtn"><i class="bi bi-plus-lg me-1"></i>Add size</button>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveVariantsBtn">Save</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const tbody = () => document.querySelector('#variantsTable tbody');
  function variantRowTpl(v={}){
    const id = v.variant_id ? Number(v.variant_id) : '';
    const size = v.size ?? '';
    const price = v.price ?? '';
    const stock = v.stock ?? 0;
    const mult = v.size_multiplier ?? 1;
    const active = (v.is_active ?? 1) ? 'checked' : '';
    return `<tr>
      <td>
        <input type="hidden" name="variant_id[]" value="${id}">
        <input type="text" class="form-control form-control-sm" name="size[]" value="${size}" required>
      </td>
      <td><input type="number" step="0.01" min="0.01" class="form-control form-control-sm" name="price[]" value="${price}" required></td>
      <td>
        <div class="mv-flex">
          <input type="number" step="1" min="0" class="form-control form-control-sm mv-stock-input" name="stock[]" value="${stock}" required>
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-success btn-sm mv-inc" data-inc="5">+5</button>
            <button type="button" class="btn btn-outline-success btn-sm mv-inc" data-inc="10">+10</button>
            <button type="button" class="btn btn-outline-success btn-sm mv-inc" data-inc="50">+50</button>
          </div>
        </div>
      </td>
      <td><input type="number" step="0.0001" min="0.0001" class="form-control form-control-sm" name="size_multiplier[]" value="${mult}" required></td>
      <td class="text-center"><input type="checkbox" class="form-check-input" name="active[]" ${active}></td>
      <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger mv-remove"><i class="bi bi-trash"></i></button></td>
    </tr>`;
  }

  function clearRows(){ tbody().innerHTML = ''; }
  function addRow(data){ tbody().insertAdjacentHTML('beforeend', variantRowTpl(data)); }

  function loadVariants(productId){
    document.getElementById('mv-product-id').value = productId;
    clearRows();
    fetch(`backend/fetch_variants.php?product_id=${encodeURIComponent(productId)}`)
      .then(r=>r.json())
      .then(json=>{
        if(!json.success){ throw new Error(json.message||'Failed to load'); }
        const list = json.variants || [];
        if(list.length===0){ addRow({}); }
        else list.forEach(v=>addRow(v));
        const modalEl = document.getElementById('manageVariantsModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
      })
      .catch(err=>{
        console.error(err);
        Swal.fire({icon:'error', title:'Error', text: 'Unable to load sizes.'});
      });

  // Bulk apply increment to all active sizes
  document.getElementById('mv-apply-bulk').addEventListener('click', function(){
    const inc = parseInt(document.getElementById('mv-bulk-inc').value || '0', 10);
    if(!(inc>0)) return;
    const rows = Array.from(tbody().querySelectorAll('tr'));
    rows.forEach(tr => {
      const active = tr.querySelector('input[name="active[]"]').checked;
      if(!active) return;
      const input = tr.querySelector('input[name="stock[]"]');
      input.value = Math.max(0, (parseInt(input.value||'0',10) + inc));
    });
  });
  }

  // expose to global
  window.openManageVariants = loadVariants;

  document.getElementById('addVariantRowBtn').addEventListener('click', ()=> addRow({}));
  tbody().addEventListener('click', function(e){
    if(e.target.closest('.mv-remove')){
      const tr = e.target.closest('tr');
      tr.parentNode.removeChild(tr);
      if (tbody().children.length===0) addRow({});
    }
    if(e.target.classList.contains('mv-inc')){
      const btn = e.target;
      const tr = btn.closest('tr');
      const input = tr.querySelector('input[name="stock[]"]');
      const inc = parseInt(btn.dataset.inc, 10) || 0;
      input.value = Math.max(0, (parseInt(input.value||'0',10) + inc));
    }
  });

  document.getElementById('saveVariantsBtn').addEventListener('click', function(){
    const productId = document.getElementById('mv-product-id').value;
    const rows = Array.from(tbody().querySelectorAll('tr'));
    if(rows.length===0){ Swal.fire({icon:'warning', title:'No sizes', text:'Add at least one size.'}); return; }
    const payload = { product_id: productId, variants: [] };
    const sizesSeen = new Set();
    for(const tr of rows){
      const variant_id = tr.querySelector('input[name="variant_id[]"]').value || null;
      const size = tr.querySelector('input[name="size[]"]').value.trim();
      const price = parseFloat(tr.querySelector('input[name="price[]"]').value);
      const stock = parseInt(tr.querySelector('input[name="stock[]"]').value, 10);
      const mult = parseFloat(tr.querySelector('input[name="size_multiplier[]"]').value);
      const active = tr.querySelector('input[name="active[]"]').checked ? 1 : 0;
      if(!size || !(price>0) || !(stock>=0) || !(mult>0)){
        Swal.fire({icon:'warning', title:'Invalid input', text:'Please check size, price, stock, and multiplier.'});
        return;
      }
      const key = size.toLowerCase();
      if(sizesSeen.has(key)){
        Swal.fire({icon:'warning', title:'Duplicate size', text:`Duplicate size: ${size}`});
        return;
      }
      sizesSeen.add(key);
      payload.variants.push({variant_id, size, price, stock, size_multiplier: mult, is_active: active});
    }
    if(!payload.variants.some(v=>v.is_active===1)){
      Swal.fire({icon:'warning', title:'No active size', text:'At least one size must be active.'});
      return;
    }

    fetch('backend/save_variants.php', {
      method:'POST',
      headers:{ 'Content-Type':'application/json' },
      body: JSON.stringify(payload)
    }).then(r=>r.json()).then(json=>{
      if(json.success){
        Swal.fire({icon:'success', title:'Saved', timer:1200, showConfirmButton:false});
        // Live update product card values without full refresh
        try {
          const pid = parseInt(productId, 10);
          const totalStock = payload.variants.filter(v=>v.is_active===1).reduce((s,v)=> s + (parseInt(v.stock,10)||0), 0);
          const minPrice = payload.variants.filter(v=>v.is_active===1).reduce((m,v)=> {
            const p = parseFloat(v.price); return (m===null || (p<m)) ? p : m;
          }, null);
          const stockEl = document.getElementById(`stock-${pid}`);
          const priceEl = document.getElementById(`price-${pid}`);
          const sizesEl = document.getElementById(`sizes-${pid}`);
          const card = document.getElementById(`product-${pid}`);
          const restockAlert = parseInt(card?.getAttribute('data-restock-alert')||'0',10);
          // Update stock text and class
          if (stockEl){
            stockEl.textContent = `${totalStock} units`;
            stockEl.classList.remove('text-danger','fw-bold','text-warning','text-success');
            if (totalStock <= 0) stockEl.classList.add('text-danger','fw-bold');
            else if (totalStock <= restockAlert) stockEl.classList.add('text-warning','fw-bold');
            else stockEl.classList.add('text-success');
          }
          // Update price
          if (priceEl && minPrice!=null){ priceEl.textContent = Number(minPrice).toFixed(2); }
          // Update size badges
          if (sizesEl){
            let html = '<strong>Size:</strong> ';
            payload.variants.filter(v=>v.is_active===1).forEach(v=>{
              const st = parseInt(v.stock,10)||0;
              const badge = st<=0 ? 'bg-danger' : (st<=restockAlert ? 'bg-warning text-dark' : 'bg-success');
              html += `<span class=\"badge ${badge} me-1\">${v.size} (${st})</span>`;
            });
            sizesEl.innerHTML = html;
          }
          // Update filter status on card
          if (card){
            const hasLow = payload.variants.some(v=>v.is_active===1 && (parseInt(v.stock,10)||0)>0 && (parseInt(v.stock,10)||0) <= restockAlert);
            const status = totalStock<=0 ? 'out_of_stock' : (hasLow ? 'low_stock' : 'normal');
            card.setAttribute('data-stock-status', status);
          }
        } catch(e){ console.warn('Live update failed:', e); }
        const modalEl = document.getElementById('manageVariantsModal');
        bootstrap.Modal.getInstance(modalEl).hide();
      } else {
        // Check if this is an insufficient materials error
        if (json.insufficient_materials && json.insufficient_materials.length > 0) {
          // Build detailed error message
          let errorHtml = `<div class="text-start">`;
          errorHtml += `<p class="text-danger">${json.message}</p>`;
          
          if (json.requested_units) {
            errorHtml += `<p><strong>Requested total units:</strong> ${json.requested_units}</p>`;
          }
          
          if (json.insufficient_materials && json.insufficient_materials.length > 0) {
            errorHtml += `<hr><strong>Material Shortage:</strong><ul class="mt-2">`;
            json.insufficient_materials.forEach(mat => {
              errorHtml += `
                <li class="mb-2">
                  <strong>${mat.name}</strong><br>
                  <small>
                    Need: ${mat.needed} | 
                    Have: ${mat.available} | 
                    <span class="text-danger">Short: ${mat.shortage}</span>
                  </small>
                </li>
              `;
            });
            errorHtml += `</ul>`;
          }
          
          errorHtml += `</div>`;
          
          const swalConfig = {
            icon: 'error',
            title: 'Stock Update Failed',
            html: errorHtml,
            width: '500px',
            showCancelButton: true,
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#6c757d'
          };
          
          // Add restock button if materials are insufficient
          if (json.insufficient_materials && json.insufficient_materials.length > 0) {
            swalConfig.confirmButtonText = 'Restock Materials';
            swalConfig.confirmButtonColor = '#2563eb';
            swalConfig.showCancelButton = true;
          } else {
            swalConfig.confirmButtonText = 'OK';
            swalConfig.showCancelButton = false;
          }
          
          Swal.fire(swalConfig).then((result) => {
            if (result.isConfirmed && json.insufficient_materials && json.insufficient_materials.length > 0) {
              // Redirect to materials management
              window.location.href = 'materialinventory.php';
            }
          });
        } else {
          // Regular error handling
          Swal.fire({icon:'error', title:'Save failed', text: json.message||'Unknown error'});
        }
      }
    }).catch(err=>{
      console.error(err);
      Swal.fire({icon:'error', title:'Request error', text:'Could not save sizes.'});
    });
  });
})();
</script>
