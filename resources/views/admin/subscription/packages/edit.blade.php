@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <a href="{{ route('admin.subscriptions.packages') }}">Packages</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Edit Package</span>
                </li>
            </ul>
        </div>

        <h3 class="page-title">Edit Package: {{ $package->package_title }}</h3>

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-body">
                        <form action="{{ route('admin.subscriptions.packages.update', $package->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group @error('package_title') has-error @enderror">
                                        <label>Package Title <span class="required">*</span></label>
                                        <input type="text" name="package_title" class="form-control" value="{{ old('package_title', $package->package_title) }}" required>
                                        @error('package_title')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group @error('package_subtitle') has-error @enderror">
                                        <label>Package Subtitle</label>
                                        <input type="text" name="package_subtitle" class="form-control" value="{{ old('package_subtitle', $package->package_subtitle) }}">
                                        @error('package_subtitle')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group @error('package_price') has-error @enderror">
                                        <label>Price (₹) <span class="required">*</span></label>
                                        <input type="number" step="0.01" name="package_price" class="form-control" value="{{ old('package_price', $package->package_price) }}" required>
                                        @error('package_price')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group @error('package_num_days') has-error @enderror">
                                        <label>Duration (Days) <span class="required">*</span></label>
                                        <input type="number" name="package_num_days" class="form-control" value="{{ old('package_num_days', $package->package_num_days) }}" required>
                                        @error('package_num_days')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group @error('package_num_listings') has-error @enderror">
                                        <label>Number of Listings <span class="required">*</span></label>
                                        <input type="number" name="package_num_listings" class="form-control" value="{{ old('package_num_listings', $package->package_num_listings) }}" required>
                                        @error('package_num_listings')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group @error('package_for') has-error @enderror">
                                        <label>Package For <span class="required">*</span></label>
                                        <select name="package_for" class="form-control" required>
                                            <option value="employer" selected>Employer</option>
                                        </select>
                                        @error('package_for')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group @error('sort_order') has-error @enderror">
                                        <label>Sort Order</label>
                                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $package->sort_order) }}">
                                        @error('sort_order')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group @error('package_features') has-error @enderror">
                                        <label>Package Features (One per line)</label>
                                        <textarea name="package_features" class="form-control" rows="5">{{ old('package_features', is_array($package->package_features) ? implode("\n", $package->package_features) : $package->package_features) }}</textarea>
                                        @error('package_features')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="status" value="1" {{ old('status', $package->status) ? 'checked' : '' }}>
                                                Active
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-success">Update Package</button>
                                <a href="{{ route('admin.subscriptions.packages') }}" class="btn btn-default">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection