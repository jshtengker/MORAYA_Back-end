<div class="container">
    <h1>This is my Home Page.</h1>

    <p>I'm <?php echo $data['name']; ?> from Korea.</p>
    <p>I'm <?php echo $data['age']; ?> years old this year.</p>
    <p>
        <h3>Daftar Dosen: </h3>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#formInsertBox">Add New Lecturer</button>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th class="text-center">ID</th>
                    <th class="text-center">Name</th>
                    <th class="text-center">E-mail</th>
                    <th class="text-center">Phone Number</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
        <?php
            $count = 1;
            foreach($data['lecturers'] as $lecs): ?>
            <tr>
                <td class="text-center"><?php echo $count; ?> </td>
                <td class="text-center"><?php echo $lecs['id']; ?> </td>
                <td class="text-center"><?php echo $lecs['name']; ?> </td>
                <td class="text-center"><?php echo $lecs['email']; ?> </td>
                <td class="text-center"><?php echo $lecs['phone']; ?> </td>
                <td class="text-center">
                  <button class="btn btn-info" data-id="<?= $lecs['id']?>" onclick="
                  $('#lec_id_update').val('<?=$lecs['id'];?>');
                  $('#lec_name_update').val('<?=$lecs['name'];?>');
                  $('#lec_email_update').val('<?=$lecs['email'];?>');
                  $('#lec_phone_update').val('<?=$lecs['phone'];?>');
                  $('#formUpdateBox').modal('show');">Edit</Button>
                    <a href="<?= APP_PATH;?>/home/delete/<?=$lecs['id'];?>" type="button" class="btn btn-danger">Delete</a>
            </td>
            </tr> <?php $count++; ?>
        <?php endforeach; ?>
        </tbody>
    </table>

    <img src="<?php echo APP_PATH; ?>/img/Chopper.png" alt="test image" width="200" class="rounded-circle shadow">

<!-- Insert Modal -->
<div class="modal" id="formInsertBox">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Insert New Data Lecturer</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <!-- Modal body -->
            <div class="modal-body">
                <form action="<?=APP_PATH;?>/home/insert" method="post">
                <div class="mb-3 mt-3"><label for="lec_id">ID: </label>
                    <input type="number" class="form-control" id="lec_id" placeholder="Enter ID" name="lec_id">
                </div>
                <div class="mb-3 mt-3"><label for="lec_name">Lecturer Name: </label>
                    <input type="text" class="form-control" id="lec_name" placeholder="Enter Name" name="lec_name">
                </div>
                <div class="mb-3 mt-3"><label for="lec_email">E-mail: </label>
                    <input type="email" class="form-control" id="lec_email" placeholder="Enter E-mail" name="lec_email">
                </div>
                <div class="mb-3"><label for="lec_phone">Phone Number: </label>
                    <input type="number" class="form-control" id="lec_phone" placeholder="Enter Phone Number" name="lec_phone">
                </div>
            </div>
            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Insert Data</button>
            </div>
            </form>
        </div>
    </div>
</div>
        
<!-- Update Modal -->
<div class="modal" id="formUpdateBox">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Update Data Lecturer</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <!-- Modal body -->
            <div class="modal-body">
                <form action="<?=APP_PATH;?>/home/update" method="post">
                <div class="mb-3 mt-3"><label for="lec_id_update">ID: </label>
                    <input type="number" class="form-control" id="lec_id_update" placeholder="Enter ID" name="lec_id_update" readonly>
                </div>
                <div class="mb-3 mt-3"><label for="lec_name_update">Lecturer Name: </label>
                    <input type="text" class="form-control" id="lec_name_update" placeholder="Enter Name" name="lec_name_update">
                </div>
                <div class="mb-3 mt-3"><label for="lec_email_update">E-mail: </label>
                    <input type="email" class="form-control" id="lec_email_update" placeholder="Enter E-mail" name="lec_email_update">
                </div>
                <div class="mb-3"><label for="lec_phone_update">Phone Number: </label>
                    <input type="number" class="form-control" id="lec_phone_update" placeholder="Enter Phone Number" name="lec_phone_update">
                </div>
            </div>
            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Update Data</button>
            </div>
            </form>
        </div>
    </div>
</div>