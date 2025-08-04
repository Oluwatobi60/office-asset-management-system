<div class="row"><!-- Begin of row for for modal-->
                    <!-- Column -->
                    <div class="col-md-12 col-lg-6 col-xlg-6"> 
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Add Role</button>
                        

                    
                        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLabel">Add User Role</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" id="basic-info-tab" data-toggle="tab" href="#basic-info" role="tab" aria-controls="basic-info" aria-selected="true">Basic Info</a>
                                            </li>
                                        </ul>
                                        <div class="tab-content" id="myTabContent">
                                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                                                <form action="" method="POSt">
                                                    <div class="row">
                                                      
                                                     
                                                     
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="category" class="col-form-label">Role:</label>
                                                                <select name="" id="category" class="form-control" >
                                                                    <option value="" selected disabled>Select Role</option>
                                                                    <option value="">User</option>
                                                                    <option value="">Admin</option>
                                                                </select>
                                                               
                                                            </div>
                                                        </div>
                                                       
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- End of column -->
                </div><!-- end of Row for modal-->