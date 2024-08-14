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

        if (xColumn && !yColumn) {
            for (const parameter in dataGroups) {
                for (const xGroup in dataGroups[parameter]) {
                    for (const yGroup in dataGroups[parameter][xGroup]) {
                        const data = dataGroups[parameter][xGroup][yGroup];
                        allXValues = allXValues.concat(extractValues(data, 'x'));
                        allYValues = allYValues.concat(extractValues(data, 'y'));
                    }
                }
            }
        } else if (!xColumn && yColumn) {
            for (const parameter in dataGroups) {
                for (const group in dataGroups[parameter]) {
                    const data = dataGroups[parameter][group];
                    allXValues = allXValues.concat(extractValues(data, 'x'));
                    allYValues = allYValues.concat(extractValues(data, 'y'));
                }
            }
        } else if (xColumn && yColumn) {
            for (const parameter in dataGroups) {
                for (const yGroup in dataGroups[parameter]) {
                    for (const xGroup in dataGroups[parameter][yGroup]) {
                        const data = dataGroups[parameter][yGroup][xGroup];
                        allXValues = allXValues.concat(extractValues(data, 'x'));
                        allYValues = allYValues.concat(extractValues(data, 'y'));
                    }
                }
            }
        } else {
            for (const parameter in dataGroups) {
                const data = dataGroups[parameter]['all'];
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

        function createCharts(groupedData, createChartFunc, marginPercentage = 0.05) {
            const { minX, maxX, minY, maxY } = getMinMaxWithMargin(groupedData, marginPercentage);
    
            for (const parameter in groupedData) {
                if (hasXColumn && hasYColumn) {
                    for (const yGroup in groupedData[parameter]) {
                        for (const xGroup in groupedData[parameter][yGroup]) {
                            const chartId = `chartXY_${parameter}_${yGroup}_${xGroup}`;
                            const canvasElement = document.getElementById(chartId);
                            if (canvasElement) {
                                const ctx = canvasElement.getContext('2d');
                                createChartFunc(ctx, groupedData[parameter][yGroup][xGroup], `${xGroup}`, minX, maxX, minY, maxY);
                            }
                        }
                    }
                } else if (hasXColumn) {
                    for (const xGroup in groupedData[parameter]) {
                        for (const yGroup in groupedData[parameter][xGroup]) {
                            const chartId = `chartXY_${parameter}_${xGroup}`;
                            const canvasElement = document.getElementById(chartId);
                            if (canvasElement) {
                                const ctx = canvasElement.getContext('2d');
                                createChartFunc(ctx, groupedData[parameter][xGroup][yGroup], `${xGroup}`, minX, maxX, minY, maxY);
                            }
                        }
                    }
                } else if (hasYColumn) {
                    for (const yGroup in groupedData[parameter]) {
                        const chartId = `chartXY_${parameter}_${yGroup}`;
                        const canvasElement = document.getElementById(chartId);
                        if (canvasElement) {
                            const ctx = canvasElement.getContext('2d');
                            createChartFunc(ctx, groupedData[parameter][yGroup], yGroup, minX, maxX, minY, maxY);
                        }
                    }
                } else {
                    const chartId = `chartXY_${parameter}_all`;
                    const canvasElement = document.getElementById(chartId);
                    if (canvasElement) {
                        const ctx = canvasElement.getContext('2d');
                        console.log(groupedData[parameter]['all']);
                        createChartFunc(ctx, groupedData[parameter]['all'], 'Line Chart', minX, maxX, minY, maxY);
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
        createCharts(groupedData, createLineChart, marginPercentage);
    });

    // Initial chart creation with the default margin
    createCharts(groupedData, createLineChart);
});
