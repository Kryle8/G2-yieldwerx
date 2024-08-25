document.addEventListener('DOMContentLoaded', () => {
    function getMinMaxWithMargin(dataGroups, marginPercentage = 0.05) {
        let allXValues = [];
        let allYValues = [];

        function extractValues(data, key) {
            if (Array.isArray(data)) {
                return data.flatMap(d => d[key] !== undefined ? [d[key]] : []);
            }
            return [];
        }

        if (hasXColumn && !hasYColumn) {
            for (const combination in dataGroups) {
                for (const xGroup in dataGroups[combination]) {
                    for (const yGroup in dataGroups[combination][xGroup]) {
                        const data = dataGroups[combination][xGroup][yGroup];
                        allXValues = allXValues.concat(extractValues(data, 'x'));
                        allYValues = allYValues.concat(extractValues(data, 'y'));
                    }
                }
            }
        } else if (!hasXColumn && hasYColumn) {
            for (const combination in dataGroups) {
                for (const yGroup in dataGroups[combination]) {
                    const data = dataGroups[combination][yGroup];
                    allXValues = allXValues.concat(extractValues(data, 'x'));
                    allYValues = allYValues.concat(extractValues(data, 'y'));
                }
            }
        } else if (hasXColumn && hasYColumn) {
            for (const combination in dataGroups) {
                for (const yGroup in dataGroups[combination]) {
                    for (const xGroup in dataGroups[combination][yGroup]) {
                        const data = dataGroups[combination][yGroup][xGroup];
                        allXValues = allXValues.concat(extractValues(data, 'x'));
                        allYValues = allYValues.concat(extractValues(data, 'y'));
                    }
                }
            }
        } else {
            for (const combination in dataGroups) {
                    const data = dataGroups[combination]['all'];
                    allXValues = allXValues.concat(extractValues(data, 'x'));
                    allYValues = allYValues.concat(extractValues(data, 'y'));
            }
        }

        const minXValue = allXValues.length > 0 ? Math.min(...allXValues) : 0;
        const maxXValue = allXValues.length > 0 ? Math.max(...allXValues) : 0;
        const minYValue = allYValues.length > 0 ? Math.min(...allYValues) : 0;
        const maxYValue = allYValues.length > 0 ? Math.max(...allYValues) : 0;

        const xMargin = (maxXValue - minXValue) * marginPercentage;
        const yMargin = (maxYValue - minYValue) * marginPercentage;

        return {
            minX: minXValue - xMargin,
            maxX: maxXValue + xMargin,
            minY: minYValue - yMargin,
            maxY: maxYValue + yMargin
        };
    }

    function createLineChart(ctx, data, label, minX, maxX, minY, maxY) {
        console.log(data);
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
                            text: xLabel
                        },
                        type: 'linear',
                        position: 'bottom',
                        min: minX,
                        max: maxX
                    },
                    y: {
                        title: {
                            display: true,
                            text: yLabel
                        },
                        min: minY,
                        max: maxY
                    }
                },
                plugins: {
                    zoom: {
                        pan: {
                            enabled: true,
                            mode: 'xy'
                        }
                    }
                }
            }
        });
    }

    function calculateCorrelation(data) {
        const n = data.length;
        if (n === 0) return { r: null, r2: null };
    
        const sumX = data.reduce((sum, point) => sum + point.x, 0);
        const sumY = data.reduce((sum, point) => sum + point.y, 0);
        const sumXY = data.reduce((sum, point) => sum + point.x * point.y, 0);
        const sumX2 = data.reduce((sum, point) => sum + point.x * point.x, 0);
        const sumY2 = data.reduce((sum, point) => sum + point.y * point.y, 0);
    
        const numerator = (n * sumXY) - (sumX * sumY);
        const denominator = Math.sqrt(((n * sumX2) - (sumX * sumX)) * ((n * sumY2) - (sumY * sumY)));
    
        if (denominator === 0) return { r: 0, r2: 0 };
    
        const r = numerator / denominator;
        const r2 = r * r;
    
        return { r, r2 };
    }
    

    function createScatterChart(ctx, data, label, minX, maxX, minY, maxY) {
        const { r, r2 } = calculateCorrelation(data);
        const correlationText = `r: ${r.toFixed(2)}, r²: ${r2.toFixed(2)}`;
    
        return new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: `${label} (${correlationText})`,
                    data: data,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    pointRadius: 2,
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
                        position: 'bottom',
                        min: minX,
                        max: maxX
                    },
                    y: {
                        title: {
                            display: true,
                            text: yLabel
                        },
                        min: minY,
                        max: maxY
                    }
                },
                plugins: {
                    zoom: {
                        pan: {
                            enabled: true,
                            mode: 'xy'
                        },
                    }
                }
            }
        });
    }

    function createCharts(groupedData, isSingleParameter, createChartFunc, marginPercentage = 0.05) {
        const { minX, maxX, minY, maxY } = getMinMaxWithMargin(groupedData, marginPercentage);

        if (isSingleParameter) {
            for (const parameter in groupedData) {
                if (hasXColumn && hasYColumn) {
                    for (const yGroup in groupedData[parameter]) {
                        const yGroupLabel = yGroup === 'No yGroup' ? 'Ungrouped' : yGroup;
                        for (const xGroup in groupedData[parameter][yGroup]) {
                            const xGroupLabel = xGroup === 'No xGroup' ? 'Ungrouped' : xGroup;
                            const chartId = `chartXY_${yGroupLabel}_${xGroupLabel}`;
                            const canvasElement = document.getElementById(chartId);
                            if (canvasElement) {
                                const ctx = canvasElement.getContext('2d');
                                createChartFunc(ctx, groupedData[parameter][yGroup][xGroup], `${xGroupLabel} vs ${yGroupLabel}`, minX, maxX, minY, maxY);
                            }
                        }
                    }
                } else if (hasXColumn && !hasYColumn) {
                    for (const xGroup in groupedData[parameter]) {
                        console.log("Xgroup: " + xGroup);
                        const xGroupLabel = xGroup === 'No xGroup' ? 'Ungrouped' : xGroup;
                        for (const yGroup in groupedData[parameter][xGroup]) {
                            const yGroupLabel = xGroup === 'No xGroup' ? 'Ungrouped' : yGroup;
                            const chartId = `chartXY_${xGroupLabel}`;
                            const canvasElement = document.getElementById(chartId);
                            if (canvasElement) {
                                const ctx = canvasElement.getContext('2d');
                                createChartFunc(ctx, groupedData[parameter][xGroup][yGroup], `${xGroupLabel}`, minX, maxX, minY, maxY);
                            }
                        }
                    }
                } else if (!hasXColumn && hasYColumn) {
                    for (const yGroup in groupedData[parameter]) {
                        const yGroupLabel = yGroup === 'No yGroup' ? 'Ungrouped' : yGroup;
                        const chartId = `chartXY_${yGroupLabel}`;
                        const canvasElement = document.getElementById(chartId);
                        if (canvasElement) {
                            const ctx = canvasElement.getContext('2d');
                            createChartFunc(ctx, groupedData[parameter][yGroup], `${yGroupLabel}`, minX, maxX, minY, maxY);
                        }
                    }
                } else {
                    const chartId = 'chartXY_all';
                    const canvasElement = document.getElementById(chartId);
                    if (canvasElement) {
                        const ctx = canvasElement.getContext('2d');
                        createChartFunc(ctx, groupedData[parameter]['all'], 'Line Chart', minX, maxX, minY, maxY);
                    }
                }
            }
        } else {
            for (const combination in groupedData) {
                if (hasXColumn && hasYColumn) {
                    for (const yGroup in groupedData[combination]) {
                        for (const xGroup in groupedData[combination][yGroup]) {
                            const chartId = `chartXY_${combination}_${yGroup}_${xGroup}`;
                            const canvasElement = document.getElementById(chartId);
                            if (canvasElement) {
                                const ctx = canvasElement.getContext('2d');
                                createChartFunc(ctx, groupedData[combination][yGroup][xGroup], `${xGroup}`, minX, maxX, minY, maxY);
                            }
                        }
                    }
                } else if (hasXColumn && !hasYColumn) {
                    console.log(groupedData[combination]);
                    for (const xGroup in groupedData[combination]) {
                        for (const yGroup in groupedData[combination][xGroup]) {
                            const chartId = `chartXY_${combination}_${xGroup}`;
                            const canvasElement = document.getElementById(chartId);
                            if (canvasElement) {
                                const ctx = canvasElement.getContext('2d');
                                createChartFunc(ctx, groupedData[combination][xGroup][yGroup], `${xGroup}`, minX, maxX, minY, maxY);
                            }
                        }
                    }
                } else if (!hasXColumn && hasYColumn) {
                    console.log(groupedData[combination]);
                    for (const yGroup in groupedData[combination]) {
                        const chartId = `chartXY_${combination}_${yGroup}`;
                        const canvasElement = document.getElementById(chartId);
                        if (canvasElement) {
                            const ctx = canvasElement.getContext('2d');
                            createChartFunc(ctx, groupedData[combination][yGroup], yGroup, minX, maxX, minY, maxY);
                        }
                    }
                } else {
                    const chartId = `chartXY_${combination}_all`;
                    const canvasElement = document.getElementById(chartId);
                    if (canvasElement) {
                        const ctx = canvasElement.getContext('2d');
                        createChartFunc(ctx, groupedData[combination]['all'], 'Line Chart', minX, maxX, minY, maxY);
                    }
                }
            }
        }
    }

    const marginRange = document.getElementById('marginRange');
    const rangeValue = document.getElementById('rangeValue');

    marginRange.addEventListener('input', function () {
        const marginPercentage = marginRange.value / 100;
        rangeValue.textContent = `${marginRange.value}%`;

        // Clear existing charts before creating new ones
        Chart.helpers.each(Chart.instances, function (instance) {
            instance.destroy();
        });

        // Recreate charts with the new margin percentage
        createCharts(groupedData, isSingleParameter, isSingleParameter ? createLineChart : createScatterChart, marginPercentage);
    });

    // Initial chart creation with the default margin
    createCharts(groupedData, isSingleParameter, isSingleParameter ? createLineChart : createScatterChart);
});
