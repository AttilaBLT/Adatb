<?php
require_once 'php/connection.php';
require_once 'php/functions.php';

printMenu();

$sql = "SELECT P.PAYMENT_ID, P.AMOUNT, P.DUE_DATE, P.METHOD, S.START_DATE, S.END_DATE,
        U.USERNAME, U.EMAIL,
        SV.SERVICE_TYPE, V.SERVER_SPECS, W.STORAGE_SPACE AS WEBSTORAGE_SIZE
        FROM ATTILA.PAYMENT P
        LEFT JOIN ATTILA.SUBSCRIPTION S ON P.SUBSCRIPTION_ID = S.ID
        LEFT JOIN ATTILA.SERVICE SV ON S.SERVICE_ID = SV.ID
        LEFT JOIN ATTILA.VPS V ON SV.VPS_ID = V.ID
        LEFT JOIN ATTILA.WEBSTORAGE W ON SV.WEBSTORAGE_ID = W.ID
        LEFT JOIN ATTILA.USERS U ON P.USER_ID = U.USER_ID
        ORDER BY P.PAYMENT_ID";

$stmt = $connect->prepare($sql);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1>Összes Számla</h1>
    
    <div class="form-group">
        <label for="payment_select">Válassz egy fizetést:</label>
        <select id="payment_select" name="payment_select" class="form-control" onchange="toggleButton()">
            <option value="">-- Válassz fizetést --</option>
            <?php foreach ($payments as $payment): ?>
                <option value="<?= $payment['PAYMENT_ID'] ?>" 
                    data-payment='<?= htmlspecialchars(json_encode($payment)) ?>'>
                    <?php
                    $serviceDetails = '';
                    if (!empty($payment['SERVER_SPECS'])) {
                        $serviceDetails .= 'VPS: ' . htmlspecialchars($payment['SERVER_SPECS']) . ' | ';
                    }
                    if (!empty($payment['WEBSTORAGE_SIZE'])) {
                        $serviceDetails .= 'Webstorage: ' . htmlspecialchars($payment['WEBSTORAGE_SIZE']) . ' MB | ';
                    }
                    echo sprintf(
                        'Fizetés #%d - %s - %s - %s Ft - %s',
                        $payment['PAYMENT_ID'],
                        htmlspecialchars($payment['USERNAME']),
                        $serviceDetails,
                        htmlspecialchars($payment['AMOUNT']),
                        htmlspecialchars($payment['DUE_DATE'])
                    );
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button id="generate_invoice" class="btn" disabled onclick="generateInvoice()">Számla generálása</button>

    <div id="invoice_details" class="invoice-container" style="display: none;">
        <h2>Számla</h2>
        <div class="invoice-header">
            <div class="company-info">
                <h3>Szolgáltató</h3>
                <p>Példa Cég Kft.</p>
                <p>1234 Budapest, Példa utca 123.</p>
                <p>Adószám: 12345678-1-23</p>
            </div>
            <div class="invoice-info">
                <p><strong>Számla sorszáma:</strong> <span id="invoice_number"></span></p>
                <p><strong>Kibocsátás dátuma:</strong> <span id="invoice_date"></span></p>
                <p><strong>Fizetési határidő:</strong> <span id="due_date"></span></p>
            </div>
        </div>
        <div class="customer-info">
            <h3>Vevő adatai</h3>
            <p><strong>Név:</strong> <span id="customer_name"></span></p>
            <p><strong>Email:</strong> <span id="customer_email"></span></p>
        </div>
        <div class="service-details">
            <h3>Szolgáltatás részletek</h3>
            <p><strong>Szolgáltatás típusa:</strong> <span id="service_type"></span></p>
            <div id="vps_details"></div>
            <div id="webstorage_details"></div>
        </div>
        <div class="payment-details">
            <h3>Fizetési részletek</h3>
            <p><strong>Fizetési mód:</strong> <span id="payment_method"></span></p>
            <p><strong>Összeg:</strong> <span id="amount"></span> Ft</p>
        </div>
    </div>
    <button id="download_invoice" class="btn" style="display: none; margin-top: 20px;" onclick="downloadInvoice()">Számla letöltése</button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
    }
    .form-control {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .btn {
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-bottom: 20px;
    }
    .btn:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }
    .invoice-container {
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 4px;
        background-color: #fff;
        margin-top: 20px;
    }
    .invoice-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #ddd;
    }
    .company-info, .invoice-info {
        flex: 1;
    }
    .customer-info, .service-details, .payment-details {
        margin-bottom: 20px;
        padding-bottom: 20px;
    }
    .customer-info, .service-details {
        border-bottom: 1px solid #ddd;
    }
    h2 {
        color: #333;
        margin-bottom: 20px;
    }
    h3 {
        color: #666;
        margin-bottom: 10px;
    }
</style>

<script>
function toggleButton() {
    const select = document.getElementById('payment_select');
    const button = document.getElementById('generate_invoice');
    button.disabled = select.value === '';
}

function generateInvoice() {
    const select = document.getElementById('payment_select');
    const selectedOption = select.options[select.selectedIndex];
    const payment = JSON.parse(selectedOption.getAttribute('data-payment'));
    
    document.getElementById('invoice_number').textContent = `INV-${payment.PAYMENT_ID}`;
    document.getElementById('invoice_date').textContent = new Date().toLocaleDateString('hu-HU');
    document.getElementById('due_date').textContent = payment.DUE_DATE;
    document.getElementById('customer_name').textContent = payment.USERNAME;
    document.getElementById('customer_email').textContent = payment.EMAIL;
    document.getElementById('service_type').textContent = payment.SERVICE_TYPE;
    
    const vpsDetails = document.getElementById('vps_details');
    if (payment.SERVER_SPECS) {
        vpsDetails.innerHTML = `<p><strong>VPS specifikáció:</strong> ${payment.SERVER_SPECS}</p>`;
    } else {
        vpsDetails.innerHTML = '';
    }
    
    const webstorageDetails = document.getElementById('webstorage_details');
    if (payment.WEBSTORAGE_SIZE) {
        webstorageDetails.innerHTML = `<p><strong>Webstorage méret:</strong> ${payment.WEBSTORAGE_SIZE} MB</p>`;
    } else {
        webstorageDetails.innerHTML = '';
    }
    
    document.getElementById('payment_method').textContent = payment.METHOD;
    document.getElementById('amount').textContent = payment.AMOUNT;
    document.getElementById('invoice_details').style.display = 'block';
    document.getElementById('download_invoice').style.display = 'block';
}

function downloadInvoice() {
    const invoiceElement = document.getElementById('invoice_details');
    const invoiceNumber = document.getElementById('invoice_number').textContent;
    
    // Show loading state
    const downloadButton = document.getElementById('download_invoice');
    const originalText = downloadButton.textContent;
    downloadButton.textContent = 'Generálás...';
    downloadButton.disabled = true;

    html2canvas(invoiceElement, {
        scale: 2, // Higher scale for better quality
        useCORS: true,
        logging: false,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jspdf.jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4'
        });

        // Calculate dimensions to fit the content properly
        const imgWidth = 210; // A4 width in mm
        const pageHeight = 297; // A4 height in mm
        const imgHeight = canvas.height * imgWidth / canvas.width;
        
        // Add the image to the PDF
        pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);

        // Save the PDF
        pdf.save(`szamla-${invoiceNumber}.pdf`);

        // Reset button state
        downloadButton.textContent = originalText;
        downloadButton.disabled = false;
    }).catch(error => {
        console.error('PDF generation failed:', error);
        downloadButton.textContent = originalText;
        downloadButton.disabled = false;
        alert('A PDF generálása sikertelen volt. Kérjük, próbálja újra.');
    });
}
</script>

<?php include 'html/footer.html'; ?>
