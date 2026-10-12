@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-buildings"></i>
                    Add Classes
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.classes') }}">Classes</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Add</li>
                    </ol>
                </nav>

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.classes') }}" class="btn btn-sm stu-top-back admin-btn-primary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>

                    <div class="master-stu-form-card">
                        <div class="stu-form-head">Add Classes</div>
                        <div class="stu-form-body">
                            <form action="#" method="POST" onsubmit="return false;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Classes Name <span class="req">*</span></label>
                                        <input type="text" class="form-control" placeholder="Enter classes name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email <span class="req">*</span></label>
                                        <input type="email" class="form-control" placeholder="Enter email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact Person <span class="req">*</span></label>
                                        <input type="text" class="form-control" placeholder="Enter contact person" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mobile Number <span class="req">*</span></label>
                                        <input type="text" class="form-control" placeholder="Enter mobile number" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" placeholder="Enter city">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">State</label>
                                        <input type="text" class="form-control" placeholder="Enter state">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pincode</label>
                                        <input type="text" class="form-control" placeholder="Enter pincode">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status <span class="req">*</span></label>
                                        <select class="form-select" required>
                                            <option value="">Select status</option>
                                            <option>Active</option>
                                            <option>Inactive</option>
                                            <option>Pending</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Full Address</label>
                                        <textarea class="form-control" rows="3" placeholder="Enter full address"></textarea>
                                    </div>
                                </div>

                                <div class="master-stu-form-actions mt-4">
                                    <button type="submit" class="btn admin-btn-primary px-4">
                                        <i class="bx bx-save me-1"></i> Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
