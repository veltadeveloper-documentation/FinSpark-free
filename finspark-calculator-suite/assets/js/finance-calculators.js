/*!
 * finance-calculators.js
 * Extended: CSV export + Inflation, Car Loan, Credit Card payoff calculators
 */
(function(){
  'use strict';

  // Localized settings coming from PHP (wp_localize_script)
  var FC = window.finsparkcsSettings || {
    currency_symbol: '$',
    currency_position: 'before',
    decimals: 2,
    locale: (navigator && navigator.language) ? navigator.language : 'en-US',
    chart_theme: 'light',
    export_csv_label: 'Export CSV'
  };

  var fcCharts = Object.create(null);
  var chartWarningShown = false;
  var elementorHookBound = false;

  /* -------------------- Helpers -------------------- */
  function getCssVar(name, fallback){
    try{ var v = getComputedStyle(document.documentElement).getPropertyValue(name).trim(); return v || fallback || ''; }
    catch(e){ return fallback || ''; }
  }

  function applyTheme(){ document.documentElement.setAttribute('data-fc-theme', (FC.chart_theme === 'dark') ? 'dark' : 'light'); }

  function moneyFormat(val){
    var decimals = Number(FC.decimals || 2);
    var symbol = FC.currency_symbol || '';
    var pos = (FC.currency_position === 'after') ? 'after' : 'before';
    var n = Number(val) || 0;
    try{
      var nf = new Intl.NumberFormat(FC.locale || undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
      var s = nf.format(n);
      return (pos === 'before') ? (symbol + s) : (s + (symbol ? (' ' + symbol) : ''));
    }catch(e){
      var s = n.toFixed(decimals);
      return (pos === 'before') ? (symbol + s) : (s + (symbol ? (' ' + symbol) : ''));
    }
  }

  function parseNum(v){
    if(v === null || v === undefined) return 0;
    var s = String(v).replace(/,/g,'').trim();
    var n = parseFloat(s);
    return isNaN(n) ? 0 : n;
  }

  function destroyChartById(id){
    if(fcCharts[id]){ try{ fcCharts[id].destroy(); }catch(e){} delete fcCharts[id]; }
  }

  function toggleChartWrap(canvas, isVisible){
    if(!canvas || !canvas.parentNode) return;
    canvas.parentNode.style.display = isVisible ? '' : 'none';
  }

  function logChartWarning(message, error){
    if(typeof window.console === 'undefined') return;
    if(!chartWarningShown && typeof window.console.warn === 'function'){
      window.console.warn('FinSpark: ' + message + ' Text and table results will continue without chart visualizations.');
      chartWarningShown = true;
    }
    if(error && typeof window.console.error === 'function'){
      window.console.error(error);
    }
  }

  function chartOptions(){
    var text = getCssVar('--fc-text', '#0b1220');
    var muted = getCssVar('--fc-muted', '#667085');
    var gridColor = getCssVar('--fc-border', 'rgba(0,0,0,0.06)');
    var tooltipBg = getCssVar('--fc-card', '#fff');
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { labels: { color: text } },
        tooltip: {
          backgroundColor: tooltipBg,
          titleColor: text,
          bodyColor: text,
          callbacks: {
            label: function(ctx){
              var v = ctx.raw;
              if (typeof v === 'number') return moneyFormat(v);
              if (v && v.y) return moneyFormat(v.y);
              return String(v);
            }
          }
        }
      },
      scales: {
        x: { ticks: { color: muted }, grid: { color: gridColor } },
        y: { ticks: { color: muted, callback: function(v){ return v.toLocaleString(); } }, grid: { color: gridColor } }
      }
    };
  }

  function createChart(canvas, config){
    if(!canvas){ return null; }
    if(!window.Chart){
      toggleChartWrap(canvas, false);
      logChartWarning('Chart.js is unavailable.');
      return null;
    }
    var context = (typeof canvas.getContext === 'function') ? canvas.getContext('2d') : null;
    if(!context){
      toggleChartWrap(canvas, false);
      logChartWarning('Unable to access the chart canvas context.');
      return null;
    }
    toggleChartWrap(canvas, true);
    var id = canvas.id || ('finsparkcs_chart_' + Math.random().toString(36).slice(2));
    destroyChartById(id);
    try{
      fcCharts[id] = new Chart(context, config);
      return fcCharts[id];
    }catch(error){
      toggleChartWrap(canvas, false);
      logChartWarning('Chart rendering failed for canvas "' + id + '".', error);
      return null;
    }
  }

  function createLineChart(canvas, labels, datasetArrayOrSingle){
    var datasets = Array.isArray(datasetArrayOrSingle) ? datasetArrayOrSingle : [datasetArrayOrSingle];
    return createChart(canvas, {
      type: 'line',
      data: { labels: labels, datasets: datasets },
      options: chartOptions()
    });
  }

  function createPieChart(canvas, labels, values, colors){
    return createChart(canvas, {
      type: 'pie',
      data: { labels: labels, datasets: [{ data: values, backgroundColor: colors }] },
      options: chartOptions()
    });
  }

  function hexToRgba(hex, alpha){
    try{
      var h = String(hex).replace('#','').trim();
      if(h.length === 3) h = h.split('').map(function(c){ return c + c; }).join('');
      var r = parseInt(h.substr(0,2),16), g = parseInt(h.substr(2,2),16), b = parseInt(h.substr(4,2),16);
      return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }catch(e){ return 'rgba(37,99,235,' + alpha + ')'; }
  }

  function ensureResultNodes(container){
    var res = container.querySelector('.fc-results');
    if(!res){
      res = document.createElement('div'); res.className='fc-results';
      container.appendChild(res);
    }
    var summary = res.querySelector('.fc-summary');
    if(!summary){
      summary = document.createElement('div'); summary.className='fc-summary';
      res.appendChild(summary);
    }
    var wrap = res.querySelector('.fc-chart-wrap');
    if(!wrap){
      wrap = document.createElement('div'); wrap.className='fc-chart-wrap';
      res.appendChild(wrap);
    }
    var exportWrap = res.querySelector('.fc-export-wrap');
    if(!exportWrap){
      exportWrap = document.createElement('div'); exportWrap.className='fc-export-wrap';
      exportWrap.style.marginTop = '8px';
      res.appendChild(exportWrap);
    }
    var canvasId = container.dataset.chartid || ('finsparkcs_chart_' + Math.random().toString(36).slice(2));
    container.dataset.chartid = canvasId;
    var canvas = wrap.querySelector('#' + canvasId);
    if(!canvas){
      canvas = document.createElement('canvas');
      canvas.id = canvasId;
      canvas.className = 'fc-chart';
      wrap.appendChild(canvas);
    }
    return { res: res, summary: summary, wrap: wrap, canvas: canvas, exportWrap: exportWrap };
  }

  /* -------------------- CSV Export -------------------- */
  function downloadCSVFromSchedule(schedule, filename){
    if(!Array.isArray(schedule) || schedule.length === 0) return;
    // build header: union of keys (keep insertion order from the first element)
    var headerKeys = Object.keys(schedule[0]);
    // ensure we include any keys appearing only in other rows
    for(var r=1;r<schedule.length;r++){
      Object.keys(schedule[r]).forEach(function(k){
        if(headerKeys.indexOf(k) === -1) headerKeys.push(k);
      });
    }

    // format rows
    var rows = schedule.map(function(row){
      return headerKeys.map(function(k){
        var v = row[k];
        if(v === null || v === undefined) return '';
        if(typeof v === 'number' && isFinite(v)){
          // numeric — use fixed decimals (but not include currency symbol)
          return Number(v).toFixed(Number(FC.decimals || 2));
        }
        if(typeof v === 'object') return JSON.stringify(v);
        return String(v);
      });
    });

    // escape CSV values and join
    function csvEscape(cell){
      if(cell === null || cell === undefined) return '';
      var s = String(cell);
      if(s.indexOf('"') !== -1) s = s.replace(/"/g, '""');
      if(s.indexOf(',') !== -1 || s.indexOf('\n') !== -1 || s.indexOf('"') !== -1) return '"' + s + '"';
      return s;
    }

    var csv = headerKeys.map(csvEscape).join(',') + '\n' + rows.map(function(r){ return r.map(csvEscape).join(','); }).join('\n');

    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filename || 'schedule.csv';
    document.body.appendChild(a);
    a.click();
    setTimeout(function(){ document.body.removeChild(a); URL.revokeObjectURL(url); }, 250);
  }

  function createExportButton(nodes, schedule, filename){
    // remove old buttons
    var existing = nodes.exportWrap.querySelector('button.fc-export-csv');
    if(existing) existing.remove();
    if(!Array.isArray(schedule) || schedule.length === 0) return;
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'fc-export-csv';
    btn.textContent = FC.export_csv_label || 'Export CSV';
    btn.style.marginRight = '8px';
    btn.addEventListener('click', function(e){
      e.preventDefault();
      downloadCSVFromSchedule(schedule, filename || 'schedule.csv');
    });
    nodes.exportWrap.appendChild(btn);
  }

  /* -------------------- Existing calculators retained -------------------- */
  // ... (Investment, Position Size, Loan, Compound, Savings, SIP, Debt, Rent vs Buy)
  // We'll re-implement key ones here while keeping previously used stable-canvas and export hooks.
  // For brevity the full code follows implementing all calculators and reusing ensureResultNodes().

  /* ---------- Investment ---------- */
  function handleInvestment(container){
    var nodes = ensureResultNodes(container);
    var initial = parseNum(container.querySelector('[name="initial"]').value);
    var monthly = parseNum(container.querySelector('[name="monthly"]').value);
    var rate = parseNum(container.querySelector('[name="rate"]').value) / 100;
    var years = parseNum(container.querySelector('[name="years"]').value);
    var comp = 12;
    var n = Math.max(0, years * comp);
    var mRate = comp > 0 ? rate / comp : 0;

    var fvSingle = (n === 0) ? initial : initial * Math.pow(1 + mRate, n);
    var fvContrib = 0;
    if(monthly === 0) fvContrib = 0;
    else if(mRate === 0) fvContrib = monthly * n;
    else fvContrib = monthly * (Math.pow(1 + mRate, n) - 1) / mRate;

    var fv = fvSingle + fvContrib;
    var totalInvested = initial + monthly * n;
    var roi = (totalInvested === 0) ? 0 : (fv - totalInvested) / totalInvested;
    var cagr = (years === 0 || totalInvested <= 0) ? 0 : Math.pow(fv / totalInvested, 1 / years) - 1;

    nodes.summary.innerHTML = '<strong>Future Value:</strong> ' + moneyFormat(fv) +
      ' &nbsp; <strong>Total Invested:</strong> ' + moneyFormat(totalInvested) +
      ' &nbsp; <strong>ROI:</strong> ' + (isFinite(roi) ? ((roi * 100).toFixed(2) + '%') : '—') +
      ' &nbsp; <strong>CAGR:</strong> ' + (isFinite(cagr) ? ((cagr * 100).toFixed(2) + '%') : '—');

    var yearsInt = Math.max(1, Math.ceil(years));
    var labels = [], data = [];
    for(var y=0;y<=yearsInt;y++){
      labels.push(String(y));
      var period = y * comp;
      var bal = 0;
      if(period === 0) bal = initial;
      else {
        if(mRate === 0) bal = initial + monthly * period;
        else bal = initial * Math.pow(1 + mRate, period) + monthly * (Math.pow(1 + mRate, period) - 1) / mRate;
      }
      data.push(Number(bal.toFixed(2)));
    }

    var canvas = nodes.canvas;
    var accent = getCssVar('--fc-accent', '#2563eb');
    var dataset = { label: 'Portfolio value', data: data, borderColor: accent, backgroundColor: hexToRgba(accent, 0.08), fill: true, tension: 0.2, pointRadius: 2 };
    createLineChart(canvas, labels, dataset);
    // no amortization table here — remove export button if any
    nodes.exportWrap.innerHTML = '';
  }

  /* ---------- Position Size ---------- */
  function handlePositionSize(container){
    var nodes = ensureResultNodes(container);
    var account = parseNum(container.querySelector('[name="account"]').value);
    var riskPct = parseNum(container.querySelector('[name="risk_pct"]').value);
    var riskAmount = parseNum(container.querySelector('[name="risk_amount"]').value);
    var entry = parseNum(container.querySelector('[name="entry"]').value);
    var stop = parseNum(container.querySelector('[name="stop"]').value);
    if(riskAmount <= 0 && riskPct > 0) riskAmount = account * (riskPct / 100);
    var perShareRisk = Math.abs(entry - stop);
    var positionSize = (perShareRisk <= 0) ? 0 : Math.floor(riskAmount / perShareRisk);
    var positionValue = positionSize * entry;

    nodes.summary.innerHTML = '<strong>Risk per trade:</strong> ' + moneyFormat(riskAmount) +
      ' &nbsp; <strong>Risk per share:</strong> ' + moneyFormat(perShareRisk) +
      ' &nbsp; <strong>Position size (shares):</strong> ' + positionSize +
      ' &nbsp; <strong>Position value:</strong> ' + moneyFormat(positionValue);

    destroyChartById(nodes.canvas.id);
    toggleChartWrap(nodes.canvas, false);
    nodes.exportWrap.innerHTML = '';
  }

  /* ---------- Loan (Mortgage) ---------- */
  function handleLoan(container){
    var nodes = ensureResultNodes(container);
    var principal = parseNum(container.querySelector('[name="principal"]').value);
    var annualRate = parseNum(container.querySelector('[name="rate"]').value) / 100;
    var years = parseNum(container.querySelector('[name="years"]').value);
    var payPerYear = parseNum(container.querySelector('[name="payments_per_year"]').value) || 12;

    var n = Math.max(0, years * payPerYear);
    var i = (payPerYear === 0) ? 0 : (annualRate / payPerYear);

    var payment = 0;
    if(n === 0) payment = 0;
    else if(i === 0) payment = principal / n;
    else payment = principal * i / (1 - Math.pow(1 + i, -n));

    var schedule = [], balance = principal, totalInterest = 0;
    for(var k=1;k<=n;k++){
      var interest = balance * i;
      var principalPaid = payment - interest;
      if(k === n) principalPaid = balance;
      balance = Math.max(0, balance - principalPaid);
      totalInterest += interest;
      schedule.push({ paymentNo: k, payment: Number(payment.toFixed(2)), principal: Number(principalPaid.toFixed(2)), interest: Number(interest.toFixed(2)), balance: Number(balance.toFixed(2)) });
    }

    nodes.summary.innerHTML = '<strong>Payment:</strong> ' + moneyFormat(payment) + ' &nbsp; <strong>Total interest:</strong> ' + moneyFormat(totalInterest) + ' &nbsp; <strong>Total paid:</strong> ' + moneyFormat(principal + totalInterest);

    // build table
    var res = nodes.res;
    var oldTable = res.querySelector('.fc-amortization');
    if(oldTable) oldTable.remove();

    var tableHtml = '<div class="fc-amortization"><table class="fc-table"><thead><tr><th>№</th><th>Payment</th><th>Principal</th><th>Interest</th><th>Balance</th></tr></thead><tbody>';
    schedule.forEach(function(row){
      tableHtml += '<tr><td style="text-align:right;">' + row.paymentNo + '</td><td>' + moneyFormat(row.payment) + '</td><td>' + moneyFormat(row.principal) + '</td><td>' + moneyFormat(row.interest) + '</td><td>' + moneyFormat(row.balance) + '</td></tr>';
    });
    tableHtml += '</tbody></table></div>';
    var tableWrap = document.createElement('div'); tableWrap.innerHTML = tableHtml;
    res.appendChild(tableWrap);

    // pie chart principal vs interest
    var canvas = nodes.canvas;
    var colors = [ getCssVar('--fc-accent', '#2563eb'), getCssVar('--fc-accent-2', '#ff8a66') ];
    createPieChart(canvas, ['Principal', 'Interest'], [principal, totalInterest], colors);

    // Export CSV for this amortization schedule
    createExportButton(nodes, schedule, 'loan-amortization.csv');
  }

  /* ---------- Compound ---------- */
  function handleCompound(container){
    var nodes = ensureResultNodes(container);
    var principal = parseNum(container.querySelector('[name="principal"]').value);
    var rate = parseNum(container.querySelector('[name="rate"]').value) / 100;
    var years = parseNum(container.querySelector('[name="years"]').value);
    var freq = parseNum(container.querySelector('[name="freq"]').value) || 12;

    var n = Math.max(0, freq * years);
    var fv = (n === 0) ? principal : principal * Math.pow(1 + rate / freq, n);
    var interestEarned = fv - principal;

    nodes.summary.innerHTML = '<strong>Future Value:</strong> ' + moneyFormat(fv) + ' &nbsp; <strong>Interest earned:</strong> ' + moneyFormat(interestEarned);

    var labels = [], data = [];
    for(var y=0;y<=Math.max(1, Math.ceil(years)); y++){
      labels.push(String(y));
      var val = principal * Math.pow(1 + rate / freq, freq * y);
      data.push(Number(val.toFixed(2)));
    }

    var canvas = nodes.canvas;
    var accent = getCssVar('--fc-accent', '#2563eb');
    var dataset = { label: 'Value over time', data: data, borderColor: accent, backgroundColor: hexToRgba(accent, 0.08), fill: true, tension: 0.2, pointRadius: 2 };
    createLineChart(canvas, labels, dataset);
    nodes.exportWrap.innerHTML = '';
  }

  /* ---------- Savings Goal ---------- */
  function denomToFVFactor(mRate, n){
    if(n === 0) return 0;
    if(mRate === 0) return n;
    return (Math.pow(1 + mRate, n) - 1) / mRate;
  }
  function handleSavingsGoal(container){
    var nodes = ensureResultNodes(container);
    var target = parseNum(container.querySelector('[name="target"]').value);
    var years = Math.max(0, parseNum(container.querySelector('[name="years"]').value));
    var rate = parseNum(container.querySelector('[name="rate"]').value)/100;
    var current = parseNum(container.querySelector('[name="current"]').value || 0);

    var comp = 12;
    var n = Math.max(0, years * comp);
    var mRate = comp > 0 ? rate / comp : 0;

    var fv_current = (n === 0 || mRate === 0) ? current : current * Math.pow(1 + mRate, n);
    var requiredFV = Math.max(0, target - fv_current);

    var monthly = 0;
    var denom = denomToFVFactor(mRate, n);
    if(n === 0) monthly = requiredFV;
    else monthly = denom === 0 ? requiredFV : (requiredFV / denom);

    var totalInvested = current + monthly * n;
    var fv = (mRate === 0) ? (current + monthly * n) : (current * Math.pow(1 + mRate, n) + monthly * denom);
    var totalInterest = fv - totalInvested;

    nodes.summary.innerHTML = '<strong>Required monthly savings:</strong> ' + moneyFormat(monthly) +
      ' &nbsp; <strong>Total invested:</strong> ' + moneyFormat(totalInvested) +
      ' &nbsp; <strong>Estimated future value:</strong> ' + moneyFormat(fv) +
      ' &nbsp; <strong>Interest:</strong> ' + moneyFormat(totalInterest);

    var yearsInt = Math.max(1, Math.ceil(years));
    var labels = [], investedData = [], valueData = [];
    for(var y=0;y<=yearsInt;y++){
      labels.push(String(y));
      if(y === 0){
        investedData.push(Number(current.toFixed(2)));
        valueData.push(Number(current.toFixed(2)));
        continue;
      }
      var months = y * comp;
      var val;
      if(mRate === 0) val = current + monthly * months;
      else val = current * Math.pow(1 + mRate, months) + monthly * (Math.pow(1 + mRate, months) - 1) / mRate;
      investedData.push(Number((current + monthly * months).toFixed(2)));
      valueData.push(Number(val.toFixed(2)));
    }

    var canvas = nodes.canvas;
    var accent = getCssVar('--fc-accent', '#2563eb');
    var dataset1 = { label: 'Contributed', data: investedData, borderColor: '#9CA3AF', backgroundColor: 'rgba(156,163,175,0.06)', fill:false, tension:0.18 };
    var dataset2 = { label: 'Value', data: valueData, borderColor:accent, backgroundColor:hexToRgba(accent,0.08), fill:true, tension:0.18 };
    createLineChart(canvas, labels, [dataset1, dataset2]);
    nodes.exportWrap.innerHTML = '';
  }

  /* ---------- SIP ---------- */
  function handleSIP(container){
    var nodes = ensureResultNodes(container);
    var monthly = parseNum(container.querySelector('[name="monthly"]').value);
    var rate = parseNum(container.querySelector('[name="rate"]').value) / 100;
    var years = Math.max(0, parseNum(container.querySelector('[name="years"]').value));

    var comp = 12;
    var n = Math.max(0, years * comp);
    var mRate = comp > 0 ? rate / comp : 0;

    var fv = 0;
    if(n === 0) fv = 0;
    else if(mRate === 0) fv = monthly * n;
    else fv = monthly * (Math.pow(1 + mRate, n) - 1) / mRate;

    var totalInvested = monthly * n;
    var profit = fv - totalInvested;

    nodes.summary.innerHTML = '<strong>Future value:</strong> ' + moneyFormat(fv) +
      ' &nbsp; <strong>Total invested:</strong> ' + moneyFormat(totalInvested) +
      ' &nbsp; <strong>Profit:</strong> ' + moneyFormat(profit);

    var yearsInt = Math.max(1, Math.ceil(years));
    var labels = [], investedData = [], valueData = [];
    for(var y=0;y<=yearsInt;y++){
      labels.push(String(y));
      var months = y * comp;
      var val = (months === 0) ? 0 : (mRate === 0 ? monthly * months : monthly * (Math.pow(1 + mRate, months) - 1) / mRate);
      investedData.push(Number((monthly * months).toFixed(2)));
      valueData.push(Number(val.toFixed(2)));
    }
    var canvas = nodes.canvas;
    var accent = getCssVar('--fc-accent', '#2563eb');
    var dsInvested = { label: 'Total invested', data: investedData, borderColor:'#9CA3AF', backgroundColor:'rgba(156,163,175,0.06)', fill:false, tension:0.18 };
    var dsValue = { label:'Portfolio value', data:valueData, borderColor:accent, backgroundColor:hexToRgba(accent,0.08), fill:true, tension:0.18 };
    createLineChart(canvas, labels, [dsInvested, dsValue]);
    nodes.exportWrap.innerHTML = '';
  }

  /* ---------- Debt Payoff (snowball/avalanche) ---------- */
  function simulateDebtPayoff(debts, budget, method){
    var maxMonths = 600;
    var month = 0;
    var schedule = [];
    var totalInterest = 0;
    var ds = debts.map(function(d){
      return { name: d.name || '', balance: Math.max(0, parseNum(d.balance)), rate: Math.max(0, parseNum(d.rate)/100), minPayment: Math.max(0, parseNum(d.minPayment)) };
    }).filter(function(d){ return d.balance > 0; });

    if(ds.length === 0) return { schedule:[], months:0, totalInterest:0, paidOff:true };

    while(month < maxMonths){
      month++;
      var bwis = ds.map(function(d){ var interest = d.balance * (d.rate / 12); return { interest: interest, balanceWithInterest: d.balance + interest }; });
      var paymentAlloc = ds.map(function(){ return 0; });
      var sumMin = 0;
      for(var i=0;i<ds.length;i++){ var p = Math.min(ds[i].minPayment, bwis[i].balanceWithInterest); paymentAlloc[i] = p; sumMin += p; }
      if(budget <= 0) return { schedule: schedule, months: month-1, totalInterest: totalInterest, paidOff:false, reason: 'budget_zero' };
      if(sumMin > 0 && sumMin > budget){
        var scale = budget / sumMin;
        for(var i2=0;i2<paymentAlloc.length;i2++) paymentAlloc[i2] = paymentAlloc[i2] * scale;
        sumMin = budget;
      }
      var extra = Math.max(0, budget - sumMin);
      if(extra > 0){
        var order = ds.map(function(_, idx){ return idx; }).filter(function(idx){ return ds[idx].balance > 0; });
        if(method === 'avalanche') order.sort(function(a,b){ return ds[b].rate - ds[a].rate; });
        else order.sort(function(a,b){ return ds[a].balance - ds[b].balance; });
        for(var j=0;j<order.length && extra > 0;j++){
          var idx = order[j];
          var remaining = bwis[idx].balanceWithInterest - paymentAlloc[idx];
          if(remaining <= 0) continue;
          var alloc = Math.min(extra, remaining);
          paymentAlloc[idx] += alloc;
          extra -= alloc;
        }
      }
      var monthPayment = 0, monthInterestThis = 0, monthPrincipalThis = 0;
      for(var k=0;k<ds.length;k++){
        var interest = bwis[k].interest;
        var pay = paymentAlloc[k];
        var interestPaid = Math.min(interest, pay);
        var principalPaid = Math.max(0, pay - interestPaid);
        ds[k].balance = Math.max(0, bwis[k].balanceWithInterest - pay);
        monthPayment += pay; monthInterestThis += interestPaid; monthPrincipalThis += principalPaid;
      }
      totalInterest += monthInterestThis;
      var totalRemaining = ds.reduce(function(acc,d){ return acc + d.balance; }, 0);
      schedule.push({ month: month, totalPayment: Number(monthPayment.toFixed(2)), interestPaid: Number(monthInterestThis.toFixed(2)), principalPaid: Number(monthPrincipalThis.toFixed(2)), totalRemaining: Number(totalRemaining.toFixed(2)), perDebt: ds.map(function(d){ return {name:d.name, balance:Number(d.balance.toFixed(2))}; })});
      var allZero = ds.every(function(d){ return d.balance <= 0.005; });
      if(allZero) return { schedule: schedule, months: month, totalInterest: totalInterest, paidOff:true };
    }
    return { schedule: schedule, months: maxMonths, totalInterest: totalInterest, paidOff:false, reason:'max_months' };
  }

  function handleDebtPayoff(container){
    var nodes = ensureResultNodes(container);
    var rows = Array.from(container.querySelectorAll('.fc-debt-row'));
    var debts = rows.map(function(row){ return { name: row.querySelector('[name="debt_name[]"]').value || '', balance: parseNum(row.querySelector('[name="debt_balance[]"]').value), rate: parseNum(row.querySelector('[name="debt_rate[]"]').value), minPayment: parseNum(row.querySelector('[name="debt_min[]"]').value) }; }).filter(function(d){ return d.balance > 0 || d.minPayment > 0; });
    var budget = parseNum(container.querySelector('[name="monthly_budget"]').value) || 0;
    var method = container.querySelector('[name="method"]').value || 'snowball';
    if(debts.length === 0){ nodes.summary.innerHTML = '<em>No debts provided.</em>'; destroyChartById(nodes.canvas.id); toggleChartWrap(nodes.canvas, false); nodes.exportWrap.innerHTML = ''; return; }
    var result = simulateDebtPayoff(debts, budget, method);
    if(!result || result.schedule.length === 0){ nodes.summary.innerHTML = '<strong>Unable to simulate payoff with this budget.</strong>'; destroyChartById(nodes.canvas.id); toggleChartWrap(nodes.canvas, false); nodes.exportWrap.innerHTML = ''; return; }
    var paidOff = result.paidOff;
    var msg = paidOff ? ('Estimated payoff in ' + Math.round(result.months/12) + ' years (' + result.months + ' months).') : ('Not paid off within ' + result.months + ' months.');
    nodes.summary.innerHTML = '<strong>' + msg + '</strong> &nbsp; <strong>Total interest:</strong> ' + moneyFormat(result.totalInterest);
    var labels = result.schedule.map(function(s){ return String(s.month); });
    var data = result.schedule.map(function(s){ return Number(s.totalRemaining); });
    var canvas = nodes.canvas;
    var accent = getCssVar('--fc-accent', '#2563eb');
    var dataset = { label: 'Total debt outstanding', data: data, borderColor: accent, backgroundColor: hexToRgba(accent, 0.06), fill:true, tension:0.2, pointRadius:0 };
    createLineChart(canvas, labels, dataset);
    var res = nodes.res;
    var oldTable = res.querySelector('.fc-amortization');
    if(oldTable) oldTable.remove();
    var tableHtml = '<div class="fc-amortization"><table class="fc-table"><thead><tr><th>Month</th><th>Payment</th><th>Interest</th><th>Principal</th><th>Total remaining</th></tr></thead><tbody>';
    result.schedule.forEach(function(r){ tableHtml += '<tr><td style="text-align:right;">' + r.month + '</td><td>' + moneyFormat(r.totalPayment) + '</td><td>' + moneyFormat(r.interestPaid) + '</td><td>' + moneyFormat(r.principalPaid) + '</td><td>' + moneyFormat(r.totalRemaining) + '</td></tr>'; });
    tableHtml += '</tbody></table></div>';
    var wrap = document.createElement('div'); wrap.innerHTML = tableHtml; res.appendChild(wrap);
    // Export csv for debt schedule
    createExportButton(nodes, result.schedule, 'debt-payoff-schedule.csv');
  }

  /* ---------- Rent vs Buy ---------- (kept from earlier) */
  function remainingMortgageBalance(loan, monthlyPayment, monthlyRate, m){
    if(loan <= 0) return 0;
    if(monthlyRate === 0) return Math.max(0, loan - monthlyPayment * m);
    var pow = Math.pow(1 + monthlyRate, m);
    return Math.max(0, loan * pow - monthlyPayment * ( (pow - 1) / monthlyRate ));
  }
  function handleRentVsBuy(container){
    var nodes = ensureResultNodes(container);
    var price = parseNum(container.querySelector('[name="home_price"]').value);
    var downPct = Math.max(0, parseNum(container.querySelector('[name="down_payment_pct"]').value)) / 100;
    var mortgageRate = parseNum(container.querySelector('[name="mortgage_rate"]').value) / 100;
    var mortgageTerm = parseNum(container.querySelector('[name="mortgage_term"]').value) || 30;
    var holdingYears = Math.max(1, parseNum(container.querySelector('[name="holding_years"]').value) || 5);
    var rentMonthly = parseNum(container.querySelector('[name="rent_month"]').value) || 0;
    var appreciation = parseNum(container.querySelector('[name="appreciation"]').value) / 100 || 0;
    var propertyTaxRate = parseNum(container.querySelector('[name="property_tax_rate"]').value) / 100 || 0;
    var maintenanceRate = parseNum(container.querySelector('[name="maintenance_rate"]').value) / 100 || 0;
    var sellingCostPct = parseNum(container.querySelector('[name="selling_cost_pct"]').value) / 100 || 0;
    var rentInflation = parseNum(container.querySelector('[name="rent_inflation"]').value) / 100 || 0;
    var downPayment = price * downPct;
    var loan = Math.max(0, price - downPayment);
    var monthlyRate = mortgageRate / 12;
    var n = mortgageTerm * 12;
    var monthlyPayment = 0;
    if(n === 0) monthlyPayment = 0;
    else if(monthlyRate === 0) monthlyPayment = loan / n;
    else monthlyPayment = loan * monthlyRate / (1 - Math.pow(1 + monthlyRate, -n));
    var labels = [], buyCosts = [], rentCosts = [];
    for(var y=1;y<=holdingYears;y++){
      var months = y * 12;
      var mortgagePaid = monthlyPayment * months;
      var propTax = price * propertyTaxRate * y;
      var maintenance = price * maintenanceRate * y;
      var salePrice = price * Math.pow(1 + appreciation, y);
      var sellingCost = salePrice * sellingCostPct;
      var remaining = remainingMortgageBalance(loan, monthlyPayment, monthlyRate, months);
      var saleProceeds = Math.max(0, salePrice - sellingCost - remaining);
      var totalPaid = downPayment + mortgagePaid + propTax + maintenance + sellingCost;
      var netCost = totalPaid - saleProceeds;
      labels.push(String(y));
      buyCosts.push(Number(netCost.toFixed(2)));
      var yearlyRent = 0;
      var rMonth = rentMonthly;
      for(var yr=1; yr<=y; yr++){ yearlyRent += rMonth * 12; rMonth = rMonth * (1 + rentInflation); }
      rentCosts.push(Number(yearlyRent.toFixed(2)));
    }
    var lastIdx = labels.length - 1;
    var buyFinal = buyCosts[lastIdx] || 0;
    var rentFinal = rentCosts[lastIdx] || 0;
    var better = (buyFinal < rentFinal) ? 'Buy looks cheaper' : ((buyFinal > rentFinal) ? 'Rent looks cheaper' : 'Tie');
    nodes.summary.innerHTML = '<strong>After ' + holdingYears + ' years:</strong> Buy net cost ' + moneyFormat(buyFinal) + ' &nbsp; Rent cost ' + moneyFormat(rentFinal) + ' &nbsp; <strong>' + better + '</strong>';
    var canvas = nodes.canvas;
    var accent = getCssVar('--fc-accent', '#2563eb');
    var accent2 = getCssVar('--fc-accent-2', '#06b6d4');
    var dsBuy = { label: 'Buy net cost', data: buyCosts, borderColor: accent, backgroundColor: hexToRgba(accent, 0.06), fill:false, tension:0.18 };
    var dsRent = { label: 'Rent cost', data: rentCosts, borderColor: accent2, backgroundColor: hexToRgba(accent2, 0.06), fill:false, tension:0.18 };
    createLineChart(canvas, labels, [dsBuy, dsRent]);
    nodes.exportWrap.innerHTML = '';
  }

  /* ---------- Compound (kept) ---------- */
  function handleCompound(container){
    var nodes = ensureResultNodes(container);
    var principal = parseNum(container.querySelector('[name="principal"]').value);
    var rate = parseNum(container.querySelector('[name="rate"]').value) / 100;
    var years = parseNum(container.querySelector('[name="years"]').value);
    var freq = parseNum(container.querySelector('[name="freq"]').value) || 12;
    var n = Math.max(0, freq * years);
    var fv = (n === 0) ? principal : principal * Math.pow(1 + rate / freq, n);
    var interestEarned = fv - principal;
    nodes.summary.innerHTML = '<strong>Future Value:</strong> ' + moneyFormat(fv) + ' &nbsp; <strong>Interest earned:</strong> ' + moneyFormat(interestEarned);
    var labels = [], data = [];
    for(var y=0;y<=Math.max(1, Math.ceil(years)); y++){
      labels.push(String(y));
      var val = principal * Math.pow(1 + rate / freq, freq * y);
      data.push(Number(val.toFixed(2)));
    }
    var canvas = nodes.canvas;
    var accent = getCssVar('--fc-accent', '#2563eb');
    var dataset = { label: 'Value over time', data: data, borderColor: accent, backgroundColor: hexToRgba(accent, 0.08), fill: true, tension: 0.2, pointRadius: 2 };
    createLineChart(canvas, labels, dataset);
    nodes.exportWrap.innerHTML = '';
  }

  /* -------------------- NEW: Inflation Calculator -------------------- */
  function handleInflation(container){
    var nodes = ensureResultNodes(container);
    var amount = parseNum(container.querySelector('[name="amount"]').value);
    var fromYear = parseInt(container.querySelector('[name="from_year"]').value, 10) || new Date().getFullYear();
    var toYear = parseInt(container.querySelector('[name="to_year"]').value, 10) || new Date().getFullYear();
    var rate = parseNum(container.querySelector('[name="inflation_rate"]').value) / 100;

    if(toYear < fromYear){
      nodes.summary.innerHTML = '<em>Note: to-year is earlier than from-year. Values will be adjusted backwards.</em>';
    }
    var years = Math.abs(toYear - fromYear);
    var direction = (toYear >= fromYear) ? 1 : -1; // future vs past

    // Build series from fromYear -> toYear
    var step = direction;
    var labels = [], data = [];
    var lastValue = amount;
    labels.push(String(fromYear));
    data.push(Number(lastValue.toFixed(2)));
    for(var y=1; y<=years; y++){
      var currentYear = fromYear + y*step;
      if(step === 1) lastValue = lastValue * (1 + rate); // inflation increases nominal amount
      else lastValue = lastValue / (1 + rate); // move backwards in time (deflate)
      labels.push(String(currentYear));
      data.push(Number(lastValue.toFixed(2)));
    }

    var finalValue = data[data.length - 1];
    nodes.summary.innerHTML = '<strong>Adjusted value for ' + toYear + ':</strong> ' + moneyFormat(finalValue);

    var canvas = nodes.canvas;
    var accent = getCssVar('--fc-accent', '#2563eb');
    var dataset = { label: 'Adjusted amount', data: data, borderColor: accent, backgroundColor: hexToRgba(accent, 0.08), fill:true, tension:0.18 };
    createLineChart(canvas, labels, dataset);
    nodes.exportWrap.innerHTML = '';
  }

  /* -------------------- NEW: Car Loan / Auto Lease Calculator -------------------- */
  function handleCarLoan(container){
    var nodes = ensureResultNodes(container);
    var price = parseNum(container.querySelector('[name="price"]').value);
    var down = parseNum(container.querySelector('[name="down"]').value);
    var annualRate = parseNum(container.querySelector('[name="rate"]').value) / 100;
    var term = Math.max(0, parseInt(container.querySelector('[name="term"]').value, 10) || 60); // months if user uses months, else convert
    var termYears = parseNum(container.querySelector('[name="term_years"]').value) || 5;
    var residual = parseNum(container.querySelector('[name="residual"]').value) || 0;
    var termMonths = term > 0 ? term : (termYears * 12);

    var loan = Math.max(0, price - down);
    var i = annualRate / 12;
    // Payment formula for loan with balloon/residual at end:
    // payment = (loan - residual / (1+i)^n) * i / (1 - (1+i)^-n)
    var n = Math.max(0, termMonths);
    var payment = 0;
    if(n === 0) payment = 0;
    else if(i === 0) payment = (loan - residual) / n;
    else {
      var pvResidual = residual / Math.pow(1 + i, n);
      var financed = loan - pvResidual;
      payment = financed * i / (1 - Math.pow(1 + i, -n));
    }

    // produce schedule
    var schedule = [], balance = loan, totalInterest = 0;
    for(var k=1;k<=n;k++){
      var interest = balance * i;
      var principalPaid = payment - interest;
      if(k === n){
        // final payment may include residual: final outstanding should equal residual discounted (we set residual as end value),
        // so last principalPaid = balance - residual
        principalPaid = Math.max(0, balance - residual);
      }
      balance = Math.max(0, balance - principalPaid);
      totalInterest += interest;
      schedule.push({ paymentNo: k, payment: Number(payment.toFixed(2)), principal: Number(principalPaid.toFixed(2)), interest: Number(interest.toFixed(2)), balance: Number(balance.toFixed(2)) });
    }

    nodes.summary.innerHTML = '<strong>Monthly payment:</strong> ' + moneyFormat(payment) + ' &nbsp; <strong>Total interest (approx):</strong> ' + moneyFormat(totalInterest);

    // show amortization table
    var res = nodes.res;
    var oldTable = res.querySelector('.fc-amortization');
    if(oldTable) oldTable.remove();
    var tableHtml = '<div class="fc-amortization"><table class="fc-table"><thead><tr><th>№</th><th>Payment</th><th>Principal</th><th>Interest</th><th>Balance</th></tr></thead><tbody>';
    schedule.forEach(function(row){ tableHtml += '<tr><td style="text-align:right;">' + row.paymentNo + '</td><td>' + moneyFormat(row.payment) + '</td><td>' + moneyFormat(row.principal) + '</td><td>' + moneyFormat(row.interest) + '</td><td>' + moneyFormat(row.balance) + '</td></tr>'; });
    tableHtml += '</tbody></table></div>';
    var tableWrap = document.createElement('div'); tableWrap.innerHTML = tableHtml;
    res.appendChild(tableWrap);

    // chart: payment composition (pie) — principal vs interest
    createPieChart(nodes.canvas, ['Principal','Interest'], [loan, totalInterest], [ getCssVar('--fc-accent','#2563eb'), getCssVar('--fc-accent-2','#ff8a66') ]);

    // export CSV
    createExportButton(nodes, schedule, 'car-loan-amortization.csv');
  }

  /* -------------------- NEW: Credit Card Payoff ---------- */
  function handleCreditCard(container){
    var nodes = ensureResultNodes(container);
    var balance = parseNum(container.querySelector('[name="balance"]').value);
    var apr = parseNum(container.querySelector('[name="rate"]').value) / 100;
    var monthlyPayment = parseNum(container.querySelector('[name="payment"]').value) || 0;

    if(monthlyPayment <= 0){
      nodes.summary.innerHTML = '<strong>Please set a monthly payment greater than 0.</strong>';
      destroyChartById(nodes.canvas.id);
      toggleChartWrap(nodes.canvas, false);
      nodes.exportWrap.innerHTML = '';
      return;
    }

    var schedule = [], totalInterest = 0, month = 0;
    var maxMonths = 600;
    var bal = balance;
    while(bal > 0.005 && month < maxMonths){
      month++;
      var interest = bal * (apr / 12);
      var pay = Math.min(monthlyPayment, bal + interest);
      var interestPaid = Math.min(interest, pay);
      var principalPaid = Math.max(0, pay - interestPaid);
      bal = Math.max(0, bal - principalPaid);
      totalInterest += interestPaid;
      schedule.push({ month: month, payment: Number(pay.toFixed(2)), interest: Number(interestPaid.toFixed(2)), principal: Number(principalPaid.toFixed(2)), balance: Number(bal.toFixed(2)) });
    }

    var paidOff = schedule.length < maxMonths;
    var monthsText = paidOff ? (schedule.length + ' months') : ('>=' + maxMonths + ' months (not paid)');
    nodes.summary.innerHTML = '<strong>Time to pay off:</strong> ' + monthsText + ' &nbsp; <strong>Total interest:</strong> ' + moneyFormat(totalInterest);

    // chart: balance vs month
    var labels = schedule.map(function(s){ return String(s.month); });
    var data = schedule.map(function(s){ return Number(s.balance); });
    var canvas = nodes.canvas;
    createLineChart(canvas, labels, { label:'Balance', data: data, borderColor: getCssVar('--fc-accent','#2563eb'), backgroundColor: hexToRgba(getCssVar('--fc-accent','#2563eb'),0.06), fill:true, tension:0.18 });

    // amortization table
    var res = nodes.res;
    var oldTable = res.querySelector('.fc-amortization');
    if(oldTable) oldTable.remove();
    var tableHtml = '<div class="fc-amortization"><table class="fc-table"><thead><tr><th>Month</th><th>Payment</th><th>Interest</th><th>Principal</th><th>Balance</th></tr></thead><tbody>';
    schedule.forEach(function(r){ tableHtml += '<tr><td style="text-align:right;">' + r.month + '</td><td>' + moneyFormat(r.payment) + '</td><td>' + moneyFormat(r.interest) + '</td><td>' + moneyFormat(r.principal) + '</td><td>' + moneyFormat(r.balance) + '</td></tr>'; });
    tableHtml += '</tbody></table></div>';
    var wrap = document.createElement('div'); wrap.innerHTML = tableHtml; res.appendChild(wrap);

    // export CSV
    createExportButton(nodes, schedule, 'creditcard-payoff.csv');
  }

  /* -------------------- DOM helpers for debt UI (reused) -------------------- */
  function addDebtRow(container, name, balance, rate, minPayment){
    var list = container.querySelector('.fc-debt-list');
    if(!list){
      list = document.createElement('div'); list.className = 'fc-debt-list';
      // insert before the buttons area (if present) or at end of form
      var form = container.querySelector('.fc-form');
      form.appendChild(list);
    }
    var row = document.createElement('div'); row.className = 'fc-debt-row';
    row.style.display = 'grid';
    row.style.gridTemplateColumns = '1fr 120px 100px 100px 32px';
    row.style.gap = '8px';
    row.style.alignItems = 'center';
    row.innerHTML = ''
      + '<input name="debt_name[]" placeholder="Name" type="text" value="' + (name || '') + '" />'
      + '<input name="debt_balance[]" placeholder="Balance" type="number" step="0.01" value="' + (balance || '') + '" />'
      + '<input name="debt_rate[]" placeholder="APR %" type="number" step="0.01" value="' + (rate || '') + '" />'
      + '<input name="debt_min[]" placeholder="Min Payment" type="number" step="0.01" value="' + (minPayment || '') + '" />'
      + '<button type="button" class="fc-debt-remove" title="Remove">✕</button>';
    list.appendChild(row);
    var btn = row.querySelector('.fc-debt-remove');
    btn.addEventListener('click', function(){ row.remove(); });
    return row;
  }

  function handleCalculatorSubmit(container){
    if(container.classList.contains('fc-investment')) handleInvestment(container);
    else if(container.classList.contains('fc-position-size')) handlePositionSize(container);
    else if(container.classList.contains('fc-loan')) handleLoan(container);
    else if(container.classList.contains('fc-compound')) handleCompound(container);
    else if(container.classList.contains('fc-savings')) handleSavingsGoal(container);
    else if(container.classList.contains('fc-sip')) handleSIP(container);
    else if(container.classList.contains('fc-debt')) handleDebtPayoff(container);
    else if(container.classList.contains('fc-rentbuy')) handleRentVsBuy(container);
    else if(container.classList.contains('fc-inflation')) handleInflation(container);
    else if(container.classList.contains('fc-car')) handleCarLoan(container);
    else if(container.classList.contains('fc-cc')) handleCreditCard(container);
  }

  function submitCalculator(container){
    var form = container ? container.querySelector('.fc-form') : null;
    if(!form) return;
    try{ form.dispatchEvent(new Event('submit')); }
    catch(e){ handleCalculatorSubmit(container); }
  }

  function getCalculatorContainers(scope){
    var containers = [];
    var root = scope || document;

    if(root === document){
      return Array.prototype.slice.call(document.querySelectorAll('.fc-calculator'));
    }

    if(root.matches && root.matches('.fc-calculator')){
      containers.push(root);
    }

    if(root.querySelectorAll){
      Array.prototype.forEach.call(root.querySelectorAll('.fc-calculator'), function(container){
        if(containers.indexOf(container) === -1){
          containers.push(container);
        }
      });
    }

    return containers;
  }

  function initCalculator(container){
    if(!container || container.nodeType !== 1 || container.dataset.fcBound === '1') return;

    var form = container.querySelector('.fc-form');
    if(!form) return;

    container.dataset.fcBound = '1';

    if(container.classList.contains('fc-debt')){
      var addBtn = container.querySelector('.fc-add-debt');
      if(addBtn && addBtn.dataset.fcBound !== '1'){
        addBtn.dataset.fcBound = '1';
        addBtn.addEventListener('click', function(e){ e.preventDefault(); addDebtRow(container,'','','',''); });
      }

      if(container.querySelectorAll('.fc-debt-row').length === 0){
        addDebtRow(container, 'Credit Card', '4000', '19.99', '100');
        addDebtRow(container, 'Car Loan', '12000', '6.5', '200');
      }
    }

    Array.prototype.forEach.call(container.querySelectorAll('[data-fc-add-row]'), function(btn){
      if(btn.dataset.fcBound === '1') return;
      btn.dataset.fcBound = '1';
      btn.addEventListener('click', function(e){ e.preventDefault(); addDebtRow(container); });
    });

    form.addEventListener('submit', function(e){
      e.preventDefault();
      handleCalculatorSubmit(container);
    });

    if(container.dataset.autorun === '1' && container.dataset.fcAutorunDone !== '1'){
      container.dataset.fcAutorunDone = '1';
      submitCalculator(container);
    }
  }

  function initCalculators(scope){
    applyTheme();
    getCalculatorContainers(scope).forEach(function(container){
      initCalculator(container);
    });
  }

  function reinitCalculators(scope){
    initCalculators(scope);
    getCalculatorContainers(scope).forEach(function(container){
      submitCalculator(container);
    });
  }

  function bindElementorHooks(){
    if(elementorHookBound || !window.elementorFrontend || !window.elementorFrontend.hooks) return;
    elementorHookBound = true;
    window.elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope){
      initCalculators($scope && $scope[0] ? $scope[0] : document);
    });
  }

  function onReady(){
    initCalculators(document);
    bindElementorHooks();

    if(window.jQuery){
      window.jQuery(window).on('elementor/frontend/init', bindElementorHooks);
    }
  }

  /* -------------------- Wire forms -------------------- */
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }

  /* -------------------- Public API -------------------- */
  window.finsparkcsCalculators = {
    setTheme: function(theme){ FC.chart_theme = (theme === 'dark') ? 'dark' : 'light'; applyTheme(); Object.keys(fcCharts).forEach(function(id){ try{ fcCharts[id].update(); }catch(e){} }); },
    init: function(scope){ initCalculators(scope || document); },
    reinit: function(scope){ reinitCalculators(scope || document); }
  };

})(); // EOF
