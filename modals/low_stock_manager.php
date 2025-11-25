<?php /* Low/Out Stock Manager for fast per-size restocking across products */ ?>
<div class="modal fade" id="lowStockManagerModal" tabindex="-1" aria-labelledby="lowStockManagerLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="lowStockManagerLabel">Low / Out Stock Manager</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
          <div class="input-group" style="max-width:260px;">
            <label class="input-group-text" for="lsm-filter">Filter</label>
            <select id="lsm-filter" class="form-select">
              <option value="low" selected>Low Stock</option>
              <option value="out">Out of Stock</option>
              <option value="all">All Active Sizes</option>
            </select>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="lsm-low-only" checked>
            <label class="form-check-label" for="lsm-low-only">Apply bulk increment to low sizes only</label>
          </div>
          <div class="input-group" style="max-width:220px;">
            <span class="input-group-text">+ Amount</span>
            <input type="number" id="lsm-bulk-inc" class="form-control" value="10" min="1">
            <button class="btn btn-success" id="lsm-apply-bulk">Apply</button>
          </div>
          <button class="btn btn-outline-secondary" id="lsm-reload">Reload</button>
        </div>

        <div class="table-responsive" style="max-height:65vh; overflow:auto;">
          <table class="table table-sm align-middle" id="lsm-table">
            <thead class="table-light">
              <tr>
                <th style="width:22%">Product</th>
                <th style="width:12%">Category</th>
                <th style="width:10%">Restock Alert</th>
                <th style="width:12%">Size</th>
                <th style="width:12%">Curr. Stock</th>
                <th style="width:12%">Add</th>
                <th style="width:12%">New Stock</th>
                <th style="width:8%">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="lsm-save">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const tbody = () => document.querySelector('#lsm-table tbody');
  const filterSel = () => document.getElementById('lsm-filter');
  let currentData = []; // array of rows {product_id, product_name, category_name, restock_alert, variant}

  function badgeClass(stock, alert){
    stock = parseInt(stock||0,10); alert = parseInt(alert||0,10);
    if(stock<=0) return 'bg-danger';
    if(stock<=alert) return 'bg-warning text-dark';
    return 'bg-success';
  }

  function rowTpl(r){
    const v = r.variant;
    const incId = `inc-${v.variant_id}`;
    const newId = `new-${v.variant_id}`;
    return `<tr data-pid="${r.product_id}" data-vid="${v.variant_id}">
      <td><strong>${r.product_name}</strong></td>
      <td>${r.category_name||''}</td>
      <td>${r.restock_alert}</td>
      <td><span class="badge ${badgeClass(v.stock, r.restock_alert)}">${v.size}</span></td>
      <td>${v.stock}</td>
      <td>
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" class="btn btn-outline-success lsm-inc" data-delta="5">+5</button>
          <button type="button" class="btn btn-outline-success lsm-inc" data-delta="10">+10</button>
          <button type="button" class="btn btn-outline-success lsm-inc" data-delta="50">+50</button>
        </div>
      </td>
      <td style="max-width:110px">
        <input type="number" class="form-control form-control-sm lsm-new" id="${newId}" value="${v.stock}" min="0">
      </td>
      <td>
        <button type="button" class="btn btn-sm btn-outline-secondary lsm-clear">Clear</button>
      </td>
    </tr>`;
  }

  function render(){
    tbody().innerHTML = currentData.map(rowTpl).join('');
  }

  function load(){
    const mode = filterSel().value;
    fetch(`backend/fetch_low_variants.php?mode=${encodeURIComponent(mode)}`)
      .then(r=>r.json())
      .then(json=>{
        if(!json.success){ throw new Error(json.message||'Failed to load'); }
        currentData = json.rows || [];
        render();
        const modalEl = document.getElementById('lowStockManagerModal');
        new bootstrap.Modal(modalEl).show();
      })
      .catch(err=>{
        console.error(err);
        Swal.fire({icon:'error', title:'Error', text: 'Unable to load variants.'});
      });
  }

  // Global opener
  window.openLowStockManager = load;

  // Events
  document.getElementById('lsm-reload').addEventListener('click', load);
  filterSel().addEventListener('change', load);
  tbody().addEventListener('click', function(e){
    if(e.target.classList.contains('lsm-inc')){
      const tr = e.target.closest('tr');
      const input = tr.querySelector('.lsm-new');
      const delta = parseInt(e.target.dataset.delta||'0', 10);
      input.value = Math.max(0, (parseInt(input.value||'0',10)+delta));
    }
    if(e.target.classList.contains('lsm-clear')){
      const tr = e.target.closest('tr');
      const vstock = parseInt(tr.children[4].textContent||'0',10);
      tr.querySelector('.lsm-new').value = vstock;
    }
  });

  document.getElementById('lsm-apply-bulk').addEventListener('click', function(){
    const inc = parseInt(document.getElementById('lsm-bulk-inc').value||'0',10);
    if(!(inc>0)) return;
    const lowOnly = document.getElementById('lsm-low-only').checked;
    const rows = Array.from(tbody().querySelectorAll('tr'));
    rows.forEach(tr=>{
      const alert = parseInt(tr.children[2].textContent||'0',10);
      const curr = parseInt(tr.children[4].textContent||'0',10);
      if(lowOnly && !(curr>0 && curr<=alert)) return;
      const input = tr.querySelector('.lsm-new');
      input.value = Math.max(0, (parseInt(input.value||'0',10)+inc));
    });
  });

  document.getElementById('lsm-save').addEventListener('click', async function(){
    // Build per product payloads using currentData, keeping price/mult/active
    const groups = new Map();
    const rows = Array.from(tbody().querySelectorAll('tr'));
    rows.forEach(tr=>{
      const pid = parseInt(tr.getAttribute('data-pid'),10);
      const vid = parseInt(tr.getAttribute('data-vid'),10);
      const newStock = parseInt(tr.querySelector('.lsm-new').value||'0',10);
      const found = currentData.find(r=>r.product_id==pid && r.variant.variant_id==vid);
      if(!found) return;
      const v = found.variant;
      const arr = groups.get(pid) || [];
      arr.push({
        variant_id: vid,
        size: v.size,
        price: v.price,
        stock: newStock,
        size_multiplier: v.size_multiplier,
        is_active: v.is_active
      });
      groups.set(pid, arr);
    });

    try {
      for (const [pid, variants] of groups.entries()){
        const payload = { product_id: pid, variants };
        const resp = await fetch('backend/save_variants.php', {
          method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
        });
        const json = await resp.json();
        if(!json.success) throw new Error(json.message||'Save failed');
      }
      Swal.fire({icon:'success', title:'Restocked', timer:1200, showConfirmButton:false});
      // Optionally close modal
      bootstrap.Modal.getInstance(document.getElementById('lowStockManagerModal')).hide();
    } catch (e) {
      console.error(e);
      Swal.fire({icon:'error', title:'Error', text:e.message||'Could not save'});
    }
  });
})();
</script>
