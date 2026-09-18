@if(session('success'))
    <div class="alert alert-success" role="alert" style="display: flex; align-items: center; gap: 10px; padding: 15px 20px; border-radius: 10px; background-color: rgba(16, 185, 129, 0.2); border: 2px solid rgba(16, 185, 129, 0.5); color: #10b981; margin-bottom: 20px; animation: slideDown 0.3s ease;">
        <i class="mdi mdi-check-circle" style="font-size: 24px;"></i>
        <span style="font-weight: 600;">{{ session('success') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger" role="alert" style="display: flex; align-items: center; gap: 10px; padding: 15px 20px; border-radius: 10px; background-color: rgba(239, 68, 68, 0.2); border: 2px solid rgba(239, 68, 68, 0.5); color: #ef4444; margin-bottom: 20px; animation: slideDown 0.3s ease;">
        <i class="mdi mdi-alert-circle" style="font-size: 24px;"></i>
        <span style="font-weight: 600;">{{ session('error') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger" role="alert" style="padding: 15px 20px; border-radius: 10px; background-color: rgba(239, 68, 68, 0.2); border: 2px solid rgba(239, 68, 68, 0.5); color: #ef4444; margin-bottom: 20px; animation: slideDown 0.3s ease;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <i class="mdi mdi-alert-circle" style="font-size: 24px;"></i>
            <span style="font-weight: 600;">Hata:</span>
        </div>
        <ul style="margin: 0; padding-left: 20px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
