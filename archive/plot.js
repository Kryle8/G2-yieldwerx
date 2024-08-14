document.addEventListener('DOMContentLoaded', function() {
    const xAxisTitle = isSingleParameter ? 'X' : xLabel;

    function createLineChart(ctx, data, label) {
        return new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [{
                    label: label,
                    data: data,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    fill: false,
                    pointRadius: 1,
                }]
            },
            options: {
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: xAxisTitle
                        },
                        type: 'linear',
                        position: 'bottom'
                    },
                    y: {
                        title: {
                            display: true,
                            text: yLabel
                        }
                    }
                }
            }
        });
    }

    function createScatterChart(ctx, data, label) {
        return new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: label,
                    data: data,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    pointRadius: 5,
                }]
            },
            options: {
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: xLabel
                        },
                        type: 'linear',
                        position: 'bottom'
                    },
                    y: {
                        title: {
                            display: true,
                            text: yLabel
                        }
                    }
                }
            }
        });
    }

    if (isSingleParameter) {
        if (groupWafer && groupProbe) {
            for (const abbrev in groupedData) {
                for (const waferID in groupedData[abbrev]) {
                    const ctx = document.getElementById('chartXY_' + abbrev + '_' + waferID);
                    if (ctx) {
                        createLineChart(ctx, groupedData[abbrev][waferID], 'Line Chart - Wafer ID: ' + waferID);
                    }
                }
            }
        } else if (groupWafer) {
            for (const waferID in groupedData) {
                const ctx = document.getElementById('chartXY_' + waferID);
                if (ctx) {
                    createLineChart(ctx, groupedData[waferID], 'Line Chart - Wafer ID: ' + waferID);
                }
            }
        } else if (groupProbe) {
            for (const abbrev in groupedData) {
                const ctx = document.getElementById('chartXY_' + abbrev);
                if (ctx) {
                    createLineChart(ctx, groupedData[abbrev], 'Line Chart - Probe Abbrev: ' + abbrev);
                }
            }
        } else {
            const ctx = document.getElementById('chartXY_all');
            if (ctx) {
                createLineChart(ctx, groupedData['all'], 'Line Chart');
            }
        }
    } else {
        if (groupWafer && groupProbe) {
            for (const abbrev in groupedData) {
                for (const waferID in groupedData[abbrev]) {
                    const ctx = document.getElementById('chartXY_' + abbrev + '_' + waferID);
                    if (ctx) {
                        createScatterChart(ctx, groupedData[abbrev][waferID], 'XY Scatter Plot - ' + waferID + '-' + abbrev);
                    }
                }
            }
        } else if (groupWafer) {
            for (const waferID in groupedData) {
                const ctx = document.getElementById('chartXY_' + waferID);
                if (ctx) {
                    createScatterChart(ctx, groupedData[waferID], 'XY Scatter Plot - Wafer ID: ' + waferID);
                }
            }
        } else if (groupProbe) {
            for (const abbrev in groupedData) {
                const ctx = document.getElementById('chartXY_' + abbrev);
                if (ctx) {
                    createScatterChart(ctx, groupedData[abbrev], 'XY Scatter Plot - Probe Abbrev: ' + abbrev);
                }
            }
        } else {
            const ctx = document.getElementById('chartXY_all');
            if (ctx) {
                createScatterChart(ctx, groupedData['all'], 'XY Scatter Plot');
            }
        }
    }
});
