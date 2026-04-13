<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Slip — {{ $item->item_name }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: #f1f5f9; padding: 30px; }

        .slip {
            max-width: 700px; margin: 0 auto; background: white;
            border: 2px solid #1e40af; border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .slip-header {
            background: linear-gradient(135deg, #003366 0%, #0d6efd 100%);
            color: white; padding: 24px 30px; text-align: center;
        }
        .slip-header h1 { font-size: 1.5rem; font-weight: 800; margin-bottom: 4px; }
        .slip-header p { opacity: 0.8; font-size: 0.85rem; }

        .slip-body { padding: 30px; }
        .slip-title { font-size: 1.1rem; font-weight: 700; color: #1e40af; margin-bottom: 16px; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px; }
        .info-item label { display: block; font-size: 0.7rem; color: #6b7280; text-transform: uppercase; font-weight: 700; margin-bottom: 2px; }
        .info-item span { font-size: 0.95rem; font-weight: 600; color: #1f2937; }

        .signatures {
            display: flex; justify-content: space-between; margin-top: 40px; padding-top: 20px;
            border-top: 1px dashed #d1d5db;
        }
        .sig-box { text-align: center; width: 40%; }
        .sig-line { border-top: 2px solid #1e293b; margin-top: 50px; padding-top: 6px; }
        .sig-label { font-size: 0.75rem; color: #6b7280; font-weight: 600; }

        .slip-footer {
            background: #f8fafc; padding: 14px 30px; text-align: center;
            border-top: 1px solid #e5e7eb; font-size: 0.72rem; color: #94a3b8;
        }

        @media print {
            body { background: white; padding: 0; }
            .slip { border: 1px solid #ddd; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="text-center mb-3 no-print" style="max-width:700px; margin: 0 auto 15px auto;">
        <button onclick="window.print()" style="background:#0d6efd; color:white; border:none; padding:10px 30px; border-radius:10px; font-weight:700; cursor:pointer; font-size:.9rem;">
            🖨️ Print Claim Slip
        </button>
        <a href="{{ route('success-log') }}" style="margin-left:10px; color:#6b7280; text-decoration:none; font-size:.85rem;">← Back to Success Log</a>
    </div>

    <div class="slip">
        <div class="slip-header">
            <h1>FoundIt! — Claim Slip</h1>
            <p>BISU Candijay Campus • Lost & Found Office</p>
        </div>

        <div class="slip-body">
            <div class="slip-title">📦 Item Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Item Name</label>
                    <span>{{ $item->item_name }}</span>
                </div>
                <div class="info-item">
                    <label>Category</label>
                    <span>{{ $item->category ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <label>Found Location</label>
                    <span>{{ $item->found_location }}</span>
                </div>
                <div class="info-item">
                    <label>Storage Location</label>
                    <span>{{ $item->storage_location ?? 'SSG Office' }}</span>
                </div>
                <div class="info-item">
                    <label>Date Found</label>
                    <span>{{ $item->found_date ? \Carbon\Carbon::parse($item->found_date)->format('F d, Y') : 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <label>Date Returned</label>
                    <span>{{ $claim?->updated_at?->format('F d, Y') ?? now()->format('F d, Y') }}</span>
                </div>
            </div>

            @if($claimer)
            <div class="slip-title">👤 Claimer Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Full Name</label>
                    <span>{{ $claimer->fname }} {{ $claimer->lname }}</span>
                </div>
                <div class="info-item">
                    <label>Student ID</label>
                    <span>{{ $claimer->student_id ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <span>{{ $claimer->email }}</span>
                </div>
                <div class="info-item">
                    <label>Contact</label>
                    <span>{{ $claimer->contact_number ?? 'N/A' }}</span>
                </div>
                @if($claimer->address)
                <div class="info-item">
                    <label>Address</label>
                    <span>{{ $claimer->address }}{{ $claimer->zipcode ? ', ' . $claimer->zipcode : '' }}</span>
                </div>
                @endif
            </div>
            @endif

            <div class="signatures">
                <div class="sig-box">
                    <div class="sig-line">
                        <div class="sig-label">Claimant's Signature</div>
                    </div>
                </div>
                <div class="sig-box">
                    <div class="sig-line">
                        <div class="sig-label">Authorized Personnel</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="slip-footer">
            Generated on {{ now()->format('F d, Y — h:i A') }} • FoundIt! Lost & Found System • BISU Candijay Campus
        </div>
    </div>
</body>
</html>
