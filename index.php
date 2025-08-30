<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Advanced EMI Calculator (jQuery)</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>    
    body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f8f9fa; display: flex; justify-content: center; padding: 40px 20px; }
    .calculator { background: #fff; width: 100%; padding: 30px; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
    h1 { text-align: center; margin-bottom: 20px; color: #333; font-size: 1.8em; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
    .form-grid input { padding: 12px 15px; font-size: 1em; border: 1px solid #ccc; border-radius: 6px; }
    button { grid-column: span 2; padding: 12px; font-size: 1.1em; background: #007bff; color: #fff; border: none; border-radius: 6px; cursor: pointer; transition: 0.3s; }
    button:hover { background: #0056b3; }
    .results { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
    .result-card { background: #f1fdf4; padding: 15px; border: 1px solid #d1f0d8; border-radius: 6px; text-align: center; min-height: 90px; }
    .result-card h3 { margin: 0 0 8px; font-size: 1.05em; color: #444; }
    .result-card p { margin: 0; font-size: 1.2em; font-weight: bold; color: #2d6a4f; }
    table { width: 100%; border-collapse: collapse; margin-top: 30px; font-size: 0.9em; }
    table th, table td { padding: 10px 8px; border: 1px solid #e0e0e0; text-align: right; }
    table th { background: #e9ecef; color: #333; }
    table td:first-child { text-align: center; font-weight: bold; color: #333; }
  </style>
</head>
<body>
  <div class="calculator">
    <h1>Advanced EMI Calculator</h1>

    <!-- Inputs -->
    <div class="form-grid">
      <label class="form-label">Loan Amount (₹)</label>
      <input type="number" class="field_value" value="1000000" id="principal" placeholder="Loan Amount (₹)" required>
      <label class="form-label">Annual Interest Rate (%)</label>
      <input type="number" class="field_value" value="9" id="rate" step="0.01" placeholder="Annual Interest Rate (%)" required>
      <label class="form-label">Tenure (Years)</label>
      <input type="number" class="field_value" value="20" id="tenure" placeholder="Tenure (Years)" required>
      <label class="form-label">Extra EMIs per Year</label>
      <input type="number" class="field_value" value="1" id="extra_emi" placeholder="Extra EMIs per Year">
      <label class="form-label">EMI Increase per Year (%)</label>
      <input type="number" class="field_value" value="5" id="emi_increase" step="0.01" placeholder="EMI Increase per Year (%)">
      <button id="calculate">Calculate</button>
    </div>

    <!-- Results -->
    <div class="results" id="results_amount">
      <div class="result-card"><h3>Loan Amount</h3><p>-</p></div>
      <div class="result-card"><h3>Initial EMI</h3><p>-</p></div>
      <div class="result-card"><h3>Total Interest</h3><p>-</p></div>
      <div class="result-card"><h3>Total Payment</h3><p>-</p></div>
      <div class="result-card"><h3>Interest Saved</h3><p>-</p></div>
      <div class="result-card"><h3>Time Saved</h3><p>-</p></div>
    </div>

    <!-- Amortization -->
    <table id="amortization" style="display:none;">
      <thead>
        <tr>
          <th>Month</th>
          <th>EMI (₹)</th>
          <th>Interest (₹)</th>
          <th>Principal (₹)</th>
          <th>Balance (₹)</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

<script>
$(document).ready(function() {
    Emi_calculation_code();
    $(".field_value").on("change", function(){
        Emi_calculation_code();
    });

    $("#calculate").on("click", function(){
        Emi_calculation_code();
    });
});

function Emi_calculation_code(){
    const INR = (num) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 2 }).format(num ?? 0);
    let P = parseFloat($("#principal").val());
    let r_annual = parseFloat($("#rate").val());
    let years = parseInt($("#tenure").val());
    let extraEmiPerYear = parseInt($("#extra_emi").val()) || 0;
    let emiIncreasePct = parseFloat($("#emi_increase").val()) || 0;

    if (isNaN(P) || isNaN(r_annual) || isNaN(years) || P <= 0 || r_annual <= 0 || years <= 0) {
      alert("Please enter valid positive numbers for Loan, Rate and Tenure.");
      return;
    }

    const i = r_annual / (12 * 100);      // monthly rate
    const N = years * 12;                  // original months

    // Base EMI (no prepayment, fixed EMI)
    const baseEmi = (P * i * Math.pow(1 + i, N)) / (Math.pow(1 + i, N) - 1);
    const baseTotalInterest = (baseEmi * N) - P;

    // Prepayment + Yearly EMI increase simulation
    let balance = P;
    let month = 0;
    let currentEmi = baseEmi;
    let totalInterest = 0;
    let rows = [];

    // Loop guard: allow up to N + 240 months to be extra safe (but should end earlier)
    while (balance > 0 && month < N + 240) {
      month++;

      // interest and principal for this month
      let interest = balance * i;
      let principalPart = currentEmi - interest;

      // if EMI exceeds remaining balance+interest in last month, pay only what's needed
      let paidEmi = currentEmi;
      if (principalPart > balance) {
        principalPart = balance;
        paidEmi = interest + principalPart;   // actual amount paid this month
      }

      balance -= principalPart;
      totalInterest += interest;

      rows.push({
        month: month,
        emi: paidEmi,
        interest: interest,
        principal: principalPart,
        balance: Math.max(balance, 0)
      });

      // Year-end adjustments:
      if (month % 12 === 0 && balance > 0) {
        const yearNo = month / 12;

        // 1) Apply extra EMIs for the year using the *current year's EMI* (not base)
        if (extraEmiPerYear > 0) {
          let extraPayment = currentEmi * extraEmiPerYear;
          if (extraPayment > balance) extraPayment = balance;
          balance -= extraPayment;

          rows.push({
            month: `Year ${yearNo} – Extra`,
            emi: extraPayment,
            interest: 0,
            principal: extraPayment,
            balance: Math.max(balance, 0)
          });
        }

        // 2) Increase EMI for *next* year
        if (emiIncreasePct > 0) {
          currentEmi = currentEmi * (1 + emiIncreasePct / 100);
        }
      }

      if (balance <= 0) break;
    }

    const newMonths = month;                         // months of regular instalments (extra rows aren't months)
    const totalPayment = P + totalInterest;          // principal + interest always equals total outflow
    const interestSaved = baseTotalInterest - totalInterest;
    const timeSaved = N - newMonths;                 // months saved

    // Update results
    $("#results_amount").html(`
      <div class="result-card"><h3>Loan Amount</h3><p>${INR(P)}</p></div>
      <div class="result-card"><h3>Initial EMI</h3><p>${INR(baseEmi)}</p></div>
      <div class="result-card"><h3>Total Interest</h3><p>${INR(totalInterest)}</p></div>
      <div class="result-card"><h3>Total Payment</h3><p>${INR(totalPayment)}</p></div>
      <div class="result-card"><h3>Interest Saved</h3><p>${INR(interestSaved)}</p></div>
      <div class="result-card"><h3>Time Saved</h3><p>${timeSaved} Months</p></div>
    `);

    // Fill amortization table
    const bodyHtml = rows.map(r => `
      <tr>
        <td>${r.month}</td>
        <td>${INR(r.emi)}</td>
        <td>${INR(r.interest)}</td>
        <td>${INR(r.principal)}</td>
        <td>${INR(r.balance)}</td>
      </tr>
    `).join("");
    $("#amortization tbody").html(bodyHtml);
    $("#amortization").show();
}
</script>
</body>
</html>