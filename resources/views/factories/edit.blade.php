@extends('layouts.app')

@section('title', 'Edit Factory')

@section('content')
<div class="settings-page">
    <div class="settings-container">
        <h1><i class="fas fa-industry"></i> Edit Factory: {{ $factoryName }}</h1>
        <p>Update description and default specifications</p>

        @if($errors->any())
            <div class="alert alert-error" style="margin-bottom: 20px;">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="settings-grid">
            <div class="settings-card">
                <h2><i class="fas fa-cog"></i> Factory Details</h2>
                <form method="POST" action="{{ route('factories.update', $factoryName) }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label>Description (optional)</label>
                        <textarea name="description" rows="3" placeholder="Short description">{{ old('description', $description) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Default Specifications</label>
                        <div id="spec-list"></div>
                        <button type="button" class="btn-theme btn-theme-primary" id="add-spec">
                            <i class="fas fa-plus"></i> Add Specification
                        </button>
                        <template id="spec-row-template">
                            <div class="spec-row">
                                <input type="text" placeholder="Key" class="spec-key">
                                <input type="text" placeholder="Value" class="spec-value">
                                <select class="spec-type">
                                    <option value="string">string</option>
                                    <option value="integer">integer</option>
                                    <option value="float">float</option>
                                    <option value="boolean">boolean</option>
                                </select>
                                <button type="button" class="btn-theme btn-theme-danger btn-sm remove-spec">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-theme btn-theme-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="{{ route('factories.index') }}" class="btn-theme btn-theme-danger">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function(){
        let index = 0;
        const list = document.getElementById('spec-list');
        const addBtn = document.getElementById('add-spec');
        const tmpl = document.getElementById('spec-row-template');
        const initialSpecs = @json(old('specifications', $specifications));

        function addRow(key = '', value = '', type = 'string'){
            const fragment = document.importNode(tmpl.content, true);
            const row = fragment.querySelector('.spec-row');
            row.querySelector('.spec-key').value = key;
            row.querySelector('.spec-value').value = value;
            row.querySelector('.spec-type').value = type;

            row.querySelector('.spec-key').setAttribute('name', `specifications[${index}][key]`);
            row.querySelector('.spec-value').setAttribute('name', `specifications[${index}][value]`);
            row.querySelector('.spec-type').setAttribute('name', `specifications[${index}][type]`);

            row.querySelector('.remove-spec').addEventListener('click', function(){
                row.remove();
            });

            list.appendChild(fragment);
            index++;
        }

        addBtn.addEventListener('click', function(){ addRow(); });
        // Populate existing
        if (Array.isArray(initialSpecs)) {
            initialSpecs.forEach(spec => addRow(spec.key ?? '', spec.value ?? '', spec.type ?? 'string'));
        }
        if (index === 0) { addRow(); }
    })();
</script>

<style>
    .spec-row { display:flex; gap:10px; margin-bottom:8px; }
    .spec-row input, .spec-row select { flex:1; padding:8px; border-radius:6px; border:1px solid #ddd; }
    .btn-theme { padding:8px 16px; border-radius:8px; font-weight:600; cursor:pointer; border:none; display:inline-flex; align-items:center; gap:8px; text-decoration:none; transition:all 0.3s ease; }
    .btn-theme-primary { background:linear-gradient(135deg, #667eea, #764ba2); color:#fff; }
    .btn-theme-danger { background:linear-gradient(135deg, #f87171, #dc2626); color:#fff; }
    .btn-sm { padding:6px 12px; font-size:0.875rem; }
    .settings-page { display:flex; justify-content:center; padding:30px; }
    .settings-container { background:rgba(255,255,255,0.95); padding:30px; border-radius:15px; box-shadow:0 8px 32px rgba(0,0,0,0.15); width:100%; max-width:900px; }
    .settings-container h1 { font-size:1.8rem; font-weight:700; margin-bottom:10px; color:#667eea; }
    .settings-grid { display:grid; grid-template-columns:1fr; gap:25px; }
    .settings-card { background:#fff; padding:25px; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.1); }
    .settings-card h2 { font-size:1.2rem; font-weight:600; margin-bottom:20px; color:#333; }
    .form-group { margin-bottom:15px; }
    .form-group label { display:block; font-weight:600; margin-bottom:6px; }
    .form-group input, .form-group select, .form-group textarea { width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:0.9rem; }
    .form-actions { display:flex; gap:10px; margin-top:20px; }
    .alert { padding:12px 20px; border-radius:10px; font-weight:600; }
    .alert-error { background:linear-gradient(135deg, #f8d7da, #f5c6cb); color:#721c24; border-left:4px solid #dc3545; }
</style>
@endsection


