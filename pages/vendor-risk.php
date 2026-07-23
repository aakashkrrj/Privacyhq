<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<title>Vendor Risk Management | PrivacyHQ</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "on-surface-variant": "#404752",
                    "on-secondary-container": "#003f6d",
                    "secondary-fixed": "#d1e4ff",
                    "outline": "#717783",
                    "surface-variant": "#e3e2e1",
                    "on-secondary": "#ffffff",
                    "surface-dim": "#dadad9",
                    "on-tertiary-fixed-variant": "#004881",
                    "error": "#ba1a1a",
                    "tertiary-fixed": "#d3e4ff",
                    "secondary": "#0061a3",
                    "on-secondary-fixed-variant": "#00497d",
                    "surface": "#faf9f8",
                    "on-primary-container": "#ffffff",
                    "on-secondary-fixed": "#001d36",
                    "tertiary-container": "#2679c9",
                    "on-surface": "#1a1c1c",
                    "surface-container": "#efeeed",
                    "surface-container-lowest": "#ffffff",
                    "surface-container-high": "#e9e8e7",
                    "secondary-fixed-dim": "#9ecaff",
                    "surface-bright": "#faf9f8",
                    "on-background": "#1a1c1c",
                    "error-container": "#ffdad6",
                    "on-tertiary": "#ffffff",
                    "background": "#faf9f8",
                    "on-primary": "#ffffff",
                    "inverse-surface": "#2f3130",
                    "tertiary": "#0060a9",
                    "on-tertiary-fixed": "#001c38",
                    "primary-container": "#0078d4",
                    "tertiary-fixed-dim": "#a2c9ff",
                    "primary-fixed": "#d3e3ff",
                    "secondary-container": "#5badff",
                    "surface-tint": "#0060ab",
                    "on-tertiary-container": "#ffffff",
                    "on-primary-fixed": "#001c39",
                    "primary": "#005faa",
                    "primary-fixed-dim": "#a3c9ff",
                    "inverse-primary": "#a3c9ff",
                    "on-error-container": "#93000a",
                    "on-primary-fixed-variant": "#004883",
                    "surface-container-low": "#f4f3f2",
                    "surface-container-highest": "#e3e2e1",
                    "inverse-on-surface": "#f1f0ef",
                    "on-error": "#ffffff",
                    "outline-variant": "#c0c7d4"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "spacing": {
                    "sm": "8px",
                    "base": "4px",
                    "stack-gap": "12px",
                    "md": "16px",
                    "xs": "4px",
                    "container-padding": "16px",
                    "lg": "24px",
                    "xl": "32px"
            },
            "fontFamily": {
                    "headline-lg-mobile": ["Inter"],
                    "headline-lg": ["Inter"],
                    "body-lg": ["Inter"],
                    "display": ["Inter"],
                    "title-md": ["Inter"],
                    "body-md": ["Inter"],
                    "caption": ["Inter"],
                    "label-md": ["Inter"]
            }
          },
        },
      }
    </script>
<style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F3F2F1;
            min-height: max(884px, 100dvh);
        }
        .fluent-card {
            background-color: #ffffff;
            border: 1px solid #EDEBE9;
            box-shadow: 0px 2px 4px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .fluent-card:hover {
            box-shadow: 0px 4px 8px rgba(0,0,0,0.06);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .heatmap-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
        }
        .heatmap-cell {
            aspect-ratio: 1;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-surface text-on-surface antialiased pb-24 md:pb-0 md:pt-16">

<!-- Header -->
<header class="fixed top-0 left-0 w-full bg-surface shadow-sm h-16 flex justify-between items-center px-container-padding z-50">
    <div class="flex items-center gap-sm">
        <span class="material-symbols-outlined text-primary text-[24px]">security</span>
        <h1 class="font-display text-headline-lg-mobile md:text-headline-lg text-primary">PrivacyHQ</h1>
    </div>
    <div class="flex items-center gap-md">
        <button class="p-2 hover:bg-surface-container-low rounded-full">
            <span class="material-symbols-outlined text-on-surface-variant">search</span>
        </button>
        <button class="p-2 hover:bg-surface-container-low rounded-full relative">
            <span class="material-symbols-outlined text-on-surface-variant">notifications</span>
            <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full"></span>
        </button>
    </div>
</header>

<div class="flex max-w-7xl mx-auto min-h-screen">
    <!-- Sidebar -->
    <aside class="hidden md:flex flex-col py-lg px-md gap-stack-gap h-full w-80 bg-surface shadow-lg rounded-r-xl sticky top-16">
        <div class="flex flex-col gap-sm mb-lg px-2">
            <div class="flex items-center gap-md">
                <img class="w-12 h-12 rounded-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBYpczt8Cuc5T1Wylg_FGNYtqvZIjb1pL2aAaMcdw3Ot3dnkhL368VfuWo7hzgJhU-B5N5RZLJ8cu9xSqahW-sPms43PCjnx3XVUNY59sxuvjDVtqSSQ9j5PB7JMjYNYL7H8psSli5xBQfu5AoCGRUuj8Wuf6TwlvItCA8tPeE--9COi9AS7__V0nFXIS-pcAaLp8UQtPxof4iV2FIE4HIQ0SFxmVrhicOGaKyq26WcKa62skbLnfBlC0xuXHgE66iA69Dj2JFTqtFU" alt="User Profile"/>
                <div class="flex flex-col">
                    <span class="font-title-md text-on-surface">Admin User</span>
                    <span class="font-label-md text-outline">Data Protection Officer</span>
                </div>
            </div>
        </div>
        <nav class="flex flex-col gap-1">
            <a class="flex items-center gap-md px-md py-sm rounded-lg text-primary font-bold bg-secondary-fixed" href="#">
                <span class="material-symbols-outlined">business_center</span>
                <span class="font-body-md">Vendor Risk</span>
            </a>
            <a class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant hover:bg-surface-container-high" href="#">
                <span class="material-symbols-outlined">search_insights</span>
                <span class="font-body-md">Data Discovery</span>
            </a>
            <a class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant hover:bg-surface-container-high" href="#">
                <span class="material-symbols-outlined">emergency_home</span>
                <span class="font-body-md">Incidents</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-container-padding md:p-lg overflow-x-hidden">
        <!-- Dashboard Header -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-md md:gap-lg mb-lg">
            <div class="md:col-span-4 fluent-card rounded-xl p-md flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-sm">
                        <span class="font-label-md text-outline">TOTAL VENDORS</span>
                        <span class="material-symbols-outlined text-primary">corporate_fare</span>
                    </div>
                    <div class="text-[48px] font-bold text-on-surface tracking-tight leading-none">142</div>
                </div>
            </div>
            <div class="md:col-span-8 fluent-card rounded-xl p-md grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <h3 class="font-title-md text-on-surface mb-sm">Risk Distribution</h3>
                    <div class="heatmap-grid">
                        <div class="heatmap-cell bg-error/10"></div>
                        <div class="heatmap-cell bg-error/40"></div>
                        <div class="heatmap-cell bg-error"></div>
                        <div class="heatmap-cell bg-secondary-container/20"></div>
                        <div class="heatmap-cell bg-secondary-container/60"></div>
                        <div class="heatmap-cell bg-error/30"></div>
                        <div class="heatmap-cell bg-green-500/10"></div>
                        <div class="heatmap-cell bg-green-500/30"></div>
                        <div class="heatmap-cell bg-green-500/60"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-md mb-lg">
            <div class="relative flex-1 max-w-md">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
                <input class="w-full pl-10 pr-4 py-2 bg-surface-container-low border border-outline-variant rounded-lg outline-none text-body-md" placeholder="Search vendors..." type="text"/>
            </div>
            <div class="flex items-center gap-sm">
                <!-- ONBOARD VENDOR BUTTON -->
                <button id="openVendorModal" type="button" class="flex items-center gap-sm px-md py-2 bg-primary text-white rounded-lg font-label-md shadow-sm hover:brightness-110">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Onboard Vendor
                </button>
            </div>
        </div>

        <!-- Vendor List -->
        <div class="fluent-card rounded-xl overflow-hidden mb-xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-md py-lg font-label-md text-outline uppercase">Vendor &amp; Service</th>
                        <th class="px-md py-lg font-label-md text-outline uppercase">Risk Score</th>
                        <th class="px-md py-lg font-label-md text-outline uppercase">Status</th>
                        <th class="px-md py-lg font-label-md text-outline uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-variant">
    <?php
    $conn = new mysqli("localhost", "root", "", "privacy_governance");
    if (!$conn->connect_error) {
        $result = $conn->query("SELECT * FROM vendors ORDER BY id DESC");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                ?>
                <tr class="hover:bg-surface-container-lowest">
                    <td class="px-md py-md font-title-md">
                        <div>
                            <div class="font-bold text-on-surface"><?php echo htmlspecialchars($row['vendor_name']); ?></div>
                            <div class="text-xs text-outline"><?php echo htmlspecialchars($row['service_type']); ?></div>
                        </div>
                    </td>
                    <td class="px-md py-md font-bold text-green-600">15</td>
                    <td class="px-md py-md">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <?php echo htmlspecialchars($row['status'] ?? 'Compliant'); ?>
                        </span>
                    </td>
                    <td class="px-md py-md text-right">
                        <button class="p-2 text-outline"><span class="material-symbols-outlined">chevron_right</span></button>
                    </td>
                </tr>
                <?php
            }
        } else {
            echo '<tr><td colspan="4" class="px-md py-md text-center text-outline">No vendors found. Add one above!</td></tr>';
        }
        $conn->close();
    }
    ?>
</tbody>
            </table>
        </div>
    </main>
</div>

<!-- Onboard Vendor Modal -->
<div id="vendorModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-surface p-6 rounded-xl shadow-xl w-full max-w-lg border border-outline-variant">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-headline-lg text-on-surface">Onboard New Vendor</h3>
            <button id="closeVendorModal" type="button" class="text-outline hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form id="vendorForm" class="flex flex-col gap-4">
            <div>
                <label class="block text-body-md font-bold mb-1">Vendor Name</label>
                <input type="text" name="vendor_name" required class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="e.g. AWS, Salesforce">
            </div>
            <div>
                <label class="block text-body-md font-bold mb-1">Service / Category</label>
                <input type="text" name="service_type" required class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="e.g. Cloud Hosting, Marketing CRM">
            </div>
            <div>
                <label class="block text-body-md font-bold mb-1">Data Shared</label>
                <input type="text" name="data_shared" required class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg" placeholder="e.g. Customer PII, Billing Info">
            </div>
            
            <div class="flex justify-end gap-sm mt-4">
                <button type="button" id="cancelVendorModal" class="px-md py-2 border border-outline-variant rounded-lg font-label-md">Cancel</button>
                <button type="submit" class="px-md py-2 bg-primary text-white rounded-lg font-label-md shadow-sm hover:brightness-110">Save Vendor</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal Controls
const modal = document.getElementById('vendorModal');
const openBtn = document.getElementById('openVendorModal');
const closeBtn = document.getElementById('closeVendorModal');
const cancelBtn = document.getElementById('cancelVendorModal');

const openModal = () => {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

const closeModal = () => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

if (openBtn) openBtn.addEventListener('click', openModal);
if (closeBtn) closeBtn.addEventListener('click', closeModal);
if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

// Close on background click
window.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
});

// Form Submission via Fetch API
document.getElementById('vendorForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);

    fetch('api/save-vendor.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            closeModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the vendor.');
    });
});
</script>

</body>
</html>