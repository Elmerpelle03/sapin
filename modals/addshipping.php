<!-- Add Rule Modal -->
<div class="modal fade" id="addRuleModal" tabindex="-1" aria-labelledby="addRuleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="backend/add_rule.php" method="POST" id="addRuleForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addRuleModalLabel">Add Shipping Rule</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="rule_name" class="form-label">Rule Name</label>
            <input type="text" class="form-control" id="rule_name" name="rule_name" required>
          </div>

          <div class="mb-3">
            <label for="fee" class="form-label">Shipping Fee (₱)</label>
            <input type="number" step="0.01" min="0" class="form-control" id="fee" name="fee" required>
          </div>

          <?php 
            $stmt = $pdo->prepare("SELECT * FROM table_region");
            $stmt->execute();
            $regions = $stmt->fetchAll();
          ?>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="region_id" class="form-label">Region</label>
              <select class="form-select" id="region" name="region_id" required>
                <option value="">Select Region</option>
                <?php foreach($regions as $row):?>
                    <option value="<?php echo $row['region_id']?>"><?php echo $row['region_name']?></option>
                <?php endforeach;?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="province_id" class="form-label">Province (optional)</label>
              <select class="form-select" id="province" name="province_id">
                <option value="">Select Province</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="municipality_id" class="form-label">Municipality (optional)</label>
              <select class="form-select" id="municipality" name="municipality_id">
                <option value="">Select Municipality</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="barangay_id" class="form-label">Barangay (optional)</label>
              <select class="form-select" id="barangay" name="barangay_id">
                <option value="">Select Barangay</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save Rule</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
    document.getElementById("region").addEventListener("change", function () {
  const regionId = this.value;
  fetch(`../backend/get_location.php?type=province&parent_id=${regionId}`)
    .then(res => res.json())
    .then(data => {
      const provinceSelect = document.getElementById("province");
      provinceSelect.innerHTML = '<option value="">Select Province</option>';
      data.forEach(item => {
        provinceSelect.innerHTML += `<option value="${item.id}">${item.name}</option>`;
      });
      document.getElementById("municipality").innerHTML = '<option value="">Select Municipality</option>';
      document.getElementById("barangay").innerHTML = '<option value="">Select Barangay</option>';
    });
});

document.getElementById("province").addEventListener("change", function () {
  const provinceId = this.value;
  fetch(`../backend/get_location.php?type=municipality&parent_id=${provinceId}`)
    .then(res => res.json())
    .then(data => {
      const municipalitySelect = document.getElementById("municipality");
      municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
      data.forEach(item => {
        municipalitySelect.innerHTML += `<option value="${item.id}">${item.name}</option>`;
      });
      document.getElementById("barangay").innerHTML = '<option value="">Select Barangay</option>';
    });
});

document.getElementById("municipality").addEventListener("change", function () {
  const municipalityId = this.value;
  fetch(`../backend/get_location.php?type=barangay&parent_id=${municipalityId}`)
    .then(res => res.json())
    .then(data => {
      const barangaySelect = document.getElementById("barangay");
      barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
      data.forEach(item => {
        barangaySelect.innerHTML += `<option value="${item.id}">${item.name}</option>`;
      });
    });
});

  </script>

  <!-- Edit Rule Modal -->
<div class="modal fade" id="editRuleModal" tabindex="-1" aria-labelledby="addRuleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="backend/edit_rule.php" method="POST" id="addRuleForm">
      <input type="text" name="rule_id" id="edit_rule_id" hidden>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addRuleModalLabel">Add Shipping Rule</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="rule_name" class="form-label">Rule Name</label>
            <input type="text" class="form-control" id="edit_rule_name" name="rule_name" required>
          </div>

          <div class="mb-3">
            <label for="fee" class="form-label">Shipping Fee (₱)</label>
            <input type="number" step="0.01" min="0" class="form-control" id="edit_shipping_fee" name="fee" required>
          </div>

          <?php 
            $stmt = $pdo->prepare("SELECT * FROM table_region");
            $stmt->execute();
            $regions = $stmt->fetchAll();
          ?>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="region_id" class="form-label">Region</label>
              <select class="form-select" id="edit_region_id" name="region_id" required>
                <option value="">Select Region</option>
                <?php foreach($regions as $row):?>
                    <option value="<?php echo $row['region_id']?>"><?php echo $row['region_name']?></option>
                <?php endforeach;?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="province_id" class="form-label">Province (optional)</label>
              <select class="form-select" id="edit_province_id" name="province_id">
                <option value="">Select Province</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="municipality_id" class="form-label">Municipality (optional)</label>
              <select class="form-select" id="edit_municipality_id" name="municipality_id">
                <option value="">Select Municipality</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="barangay_id" class="form-label">Barangay (optional)</label>
              <select class="form-select" id="edit_barangay_id" name="barangay_id">
                <option value="">Select Barangay</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save Rule</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
  const editModal = new bootstrap.Modal(document.getElementById('editRuleModal'));

  async function fetchAndPopulate(type, parentId, selectId, selectedValue = '') {
    const res = await fetch(`../backend/get_location.php?type=${type}&parent_id=${parentId}`);
    const data = await res.json();

    const select = document.getElementById(selectId);
    select.innerHTML = `<option value="">Select ${type.charAt(0).toUpperCase() + type.slice(1)}</option>`;
    data.forEach(item => {
      const option = document.createElement('option');
      option.value = item.id;
      option.textContent = item.name;
      if (item.id == selectedValue) {
        option.selected = true;
      }
      select.appendChild(option);
    });
  }

  document.addEventListener('click', async function (e) {
    if (e.target.classList.contains('edit-btn')) {
      const btn = e.target;

      // Fill basic fields
      document.getElementById('edit_rule_id').value = btn.getAttribute('data-id');
      document.getElementById('edit_rule_name').value = btn.getAttribute('data-name');
      document.getElementById('edit_shipping_fee').value = btn.getAttribute('data-fee');

      // Get all IDs
      const regionId = btn.getAttribute('data-region');
      const provinceId = btn.getAttribute('data-province');
      const municipalityId = btn.getAttribute('data-municipality');
      const barangayId = btn.getAttribute('data-barangay');

      // Chain population
      document.getElementById('edit_region_id').value = regionId;
      await fetchAndPopulate('province', regionId, 'edit_province_id', provinceId);
      await fetchAndPopulate('municipality', provinceId, 'edit_municipality_id', municipalityId);
      await fetchAndPopulate('barangay', municipalityId, 'edit_barangay_id', barangayId);

      // Show modal
      editModal.show();
    }
  });

  // Also add on-change cascading events for manual changes:
  document.getElementById('edit_region_id').addEventListener('change', async function () {
    await fetchAndPopulate('province', this.value, 'edit_province_id');
    document.getElementById('edit_municipality_id').innerHTML = '<option value="">Select Municipality</option>';
    document.getElementById('edit_barangay_id').innerHTML = '<option value="">Select Barangay</option>';
  });

  document.getElementById('edit_province_id').addEventListener('change', async function () {
    await fetchAndPopulate('municipality', this.value, 'edit_municipality_id');
    document.getElementById('edit_barangay_id').innerHTML = '<option value="">Select Barangay</option>';
  });

  document.getElementById('edit_municipality_id').addEventListener('change', async function () {
    await fetchAndPopulate('barangay', this.value, 'edit_barangay_id');
  });
});

</script>