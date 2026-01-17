(function ($) {
    "use strict";

    class CuttingOptimizer {
        constructor(boxWidth, boxHeight, sheetWidth, sheetHeight, gap = 0.3) {
            this.boxWidth = boxWidth;
            this.boxHeight = boxHeight;
            this.sheetWidth = sheetWidth;
            this.sheetHeight = sheetHeight;
            this.gap = gap;
            this.boxArea = boxWidth * boxHeight;
            this.sheetArea = sheetWidth * sheetHeight;
        }

        // Recursively fill remaining space
        fillRemainingSpace(remainingWidth, remainingHeight, boxW, boxH, depth = 0) {
            if (depth > 3 || remainingWidth < Math.min(boxW, boxH) + this.gap ||
                remainingHeight < Math.min(boxW, boxH) + this.gap) {
                return [];
            }

            const effectiveBoxW = boxW + this.gap;
            const effectiveBoxH = boxH + this.gap;

            let bestConfig = { totalBoxes: 0, details: [] };

            // Try original orientation (boxW × boxH)
            const cols1 = Math.floor(remainingWidth / effectiveBoxW);
            const rows1 = Math.floor(remainingHeight / effectiveBoxH);
            const boxes1 = cols1 * rows1;

            if (boxes1 > 0) {
                const usedW1 = cols1 * effectiveBoxW - this.gap;
                const usedH1 = rows1 * effectiveBoxH - this.gap;

                const config1 = {
                    totalBoxes: boxes1,
                    details: [{
                        boxes: boxes1,
                        strips: cols1,
                        boxesPerStrip: rows1,
                        orientation: `${boxW}×${boxH}`,
                        isRotated: false,
                        usedWidth: usedW1,
                        usedHeight: usedH1,
                    }]
                };

                // Try to fill remaining spaces recursively
                const rightSpace = this.fillRemainingSpace(
                    remainingWidth - usedW1 - this.gap,
                    usedH1,
                    boxW, boxH,
                    depth + 1
                );

                const bottomSpace = this.fillRemainingSpace(
                    usedW1,
                    remainingHeight - usedH1 - this.gap,
                    boxW, boxH,
                    depth + 1
                );

                const cornerSpace = this.fillRemainingSpace(
                    remainingWidth - usedW1 - this.gap,
                    remainingHeight - usedH1 - this.gap,
                    boxW, boxH,
                    depth + 1
                );

                config1.totalBoxes += rightSpace.reduce((sum, d) => sum + d.boxes, 0);
                config1.totalBoxes += bottomSpace.reduce((sum, d) => sum + d.boxes, 0);
                config1.totalBoxes += cornerSpace.reduce((sum, d) => sum + d.boxes, 0);
                config1.details = [...config1.details, ...rightSpace, ...bottomSpace, ...cornerSpace];

                if (config1.totalBoxes > bestConfig.totalBoxes) {
                    bestConfig = config1;
                }
            }

            // Try rotated orientation (boxH × boxW)
            const cols2 = Math.floor(remainingWidth / effectiveBoxH);
            const rows2 = Math.floor(remainingHeight / effectiveBoxW);
            const boxes2 = cols2 * rows2;

            if (boxes2 > 0) {
                const usedW2 = cols2 * effectiveBoxH - this.gap;
                const usedH2 = rows2 * effectiveBoxW - this.gap;

                const config2 = {
                    totalBoxes: boxes2,
                    details: [{
                        boxes: boxes2,
                        strips: cols2,
                        boxesPerStrip: rows2,
                        orientation: `${boxH}×${boxW}`,
                        isRotated: true,
                        usedWidth: usedW2,
                        usedHeight: usedH2,
                    }]
                };

                // Try to fill remaining spaces recursively
                const rightSpace = this.fillRemainingSpace(
                    remainingWidth - usedW2 - this.gap,
                    usedH2,
                    boxW, boxH,
                    depth + 1
                );

                const bottomSpace = this.fillRemainingSpace(
                    usedW2,
                    remainingHeight - usedH2 - this.gap,
                    boxW, boxH,
                    depth + 1
                );

                const cornerSpace = this.fillRemainingSpace(
                    remainingWidth - usedW2 - this.gap,
                    remainingHeight - usedH2 - this.gap,
                    boxW, boxH,
                    depth + 1
                );

                config2.totalBoxes += rightSpace.reduce((sum, d) => sum + d.boxes, 0);
                config2.totalBoxes += bottomSpace.reduce((sum, d) => sum + d.boxes, 0);
                config2.totalBoxes += cornerSpace.reduce((sum, d) => sum + d.boxes, 0);
                config2.details = [...config2.details, ...rightSpace, ...bottomSpace, ...cornerSpace];

                if (config2.totalBoxes > bestConfig.totalBoxes) {
                    bestConfig = config2;
                }
            }

            return bestConfig.details;
        }

        // Calculate layout with specific number of main strips
        calculateStripLayout(boxW, boxH, isVertical) {
            const effectiveBoxW = boxW + this.gap;
            const effectiveBoxH = boxH + this.gap;

            let allResults = [];

            if (isVertical) {
                // Vertical strips: boxes oriented as boxW × boxH
                const maxStrips = Math.floor(this.sheetWidth / effectiveBoxW);
                const boxesPerStrip = Math.floor(this.sheetHeight / effectiveBoxH);

                // Try different numbers of strips
                for (let numStrips = 0; numStrips <= maxStrips; numStrips++) {
                    const usedWidth = numStrips > 0 ? numStrips * effectiveBoxW - this.gap : 0;
                    const usedHeight = boxesPerStrip > 0 ? boxesPerStrip * effectiveBoxH - this.gap : 0;
                    const mainBoxes = numStrips * boxesPerStrip;

                    const remainingWidth = this.sheetWidth - (usedWidth > 0 ? usedWidth + this.gap : 0);
                    const remainingHeight = this.sheetHeight;

                    // Recursively fill remaining space
                    const remainingDetails = this.fillRemainingSpace(
                        remainingWidth,
                        remainingHeight,
                        boxW,
                        boxH,
                        0
                    );

                    const rotatedBoxes = remainingDetails.reduce((sum, d) => sum + d.boxes, 0);
                    const totalBoxes = mainBoxes + rotatedBoxes;

                    // Calculate total used dimensions
                    let totalUsedWidth = usedWidth;
                    let totalUsedHeight = usedHeight;

                    remainingDetails.forEach(detail => {
                        totalUsedWidth = Math.max(totalUsedWidth, usedWidth + (usedWidth > 0 ? this.gap : 0) + detail.usedWidth);
                        totalUsedHeight = Math.max(totalUsedHeight, detail.usedHeight);
                    });

                    allResults.push({
                        numStrips,
                        boxesPerStrip,
                        mainBoxes,
                        remainingDetails,
                        rotatedBoxes,
                        totalBoxes,
                        usedWidth: totalUsedWidth,
                        usedHeight: totalUsedHeight,
                        mainOrientation: `${boxW}×${boxH}`,
                        layoutType: 'vertical',
                    });
                }
            } else {
                // Horizontal strips: boxes oriented as boxW × boxH
                const maxStrips = Math.floor(this.sheetHeight / effectiveBoxH);
                const boxesPerStrip = Math.floor(this.sheetWidth / effectiveBoxW);

                // Try different numbers of strips
                for (let numStrips = 0; numStrips <= maxStrips; numStrips++) {
                    const usedWidth = boxesPerStrip > 0 ? boxesPerStrip * effectiveBoxW - this.gap : 0;
                    const usedHeight = numStrips > 0 ? numStrips * effectiveBoxH - this.gap : 0;
                    const mainBoxes = numStrips * boxesPerStrip;

                    const remainingWidth = this.sheetWidth;
                    const remainingHeight = this.sheetHeight - (usedHeight > 0 ? usedHeight + this.gap : 0);

                    // Recursively fill remaining space
                    const remainingDetails = this.fillRemainingSpace(
                        remainingWidth,
                        remainingHeight,
                        boxW,
                        boxH,
                        0
                    );

                    const rotatedBoxes = remainingDetails.reduce((sum, d) => sum + d.boxes, 0);
                    const totalBoxes = mainBoxes + rotatedBoxes;

                    // Calculate total used dimensions
                    let totalUsedWidth = usedWidth;
                    let totalUsedHeight = usedHeight;

                    remainingDetails.forEach(detail => {
                        totalUsedWidth = Math.max(totalUsedWidth, detail.usedWidth);
                        totalUsedHeight = Math.max(totalUsedHeight, usedHeight + (usedHeight > 0 ? this.gap : 0) + detail.usedHeight);
                    });

                    allResults.push({
                        numStrips,
                        boxesPerStrip,
                        mainBoxes,
                        remainingDetails,
                        rotatedBoxes,
                        totalBoxes,
                        usedWidth: totalUsedWidth,
                        usedHeight: totalUsedHeight,
                        mainOrientation: `${boxW}×${boxH}`,
                        layoutType: 'horizontal',
                    });
                }
            }

            return allResults;
        }

        calculateAllLayouts() {
            const layouts = [];

            // Combination 1: Original box orientation (boxWidth × boxHeight)
            const layout1A = this.calculateStripLayout(this.boxWidth, this.boxHeight, true);
            layout1A.forEach(layout => {
                layouts.push({
                    name: `Box ${this.boxWidth}×${this.boxHeight} - Vertical Strips`,
                    boxWidth: this.boxWidth,
                    boxHeight: this.boxHeight,
                    ...layout,
                });
            });

            const layout1B = this.calculateStripLayout(this.boxWidth, this.boxHeight, false);
            layout1B.forEach(layout => {
                layouts.push({
                    name: `Box ${this.boxWidth}×${this.boxHeight} - Horizontal Strips`,
                    boxWidth: this.boxWidth,
                    boxHeight: this.boxHeight,
                    ...layout,
                });
            });

            // Combination 2: Rotated box orientation (boxHeight × boxWidth)
            const layout2A = this.calculateStripLayout(this.boxHeight, this.boxWidth, true);
            layout2A.forEach(layout => {
                layouts.push({
                    name: `Box ${this.boxHeight}×${this.boxWidth} - Vertical Strips`,
                    boxWidth: this.boxHeight,
                    boxHeight: this.boxWidth,
                    ...layout,
                });
            });

            const layout2B = this.calculateStripLayout(this.boxHeight, this.boxWidth, false);
            layout2B.forEach(layout => {
                layouts.push({
                    name: `Box ${this.boxHeight}×${this.boxWidth} - Horizontal Strips`,
                    boxWidth: this.boxHeight,
                    boxHeight: this.boxWidth,
                    ...layout,
                });
            });

            return layouts;
        }

        findOptimalLayout() {
            const layouts = this.calculateAllLayouts();

            const layoutsWithMetrics = layouts.map((layout) => {
                const usedArea = layout.totalBoxes * this.boxArea;
                const wastedArea = this.sheetArea - usedArea;
                const efficiency = (usedArea / this.sheetArea) * 100;

                return {
                    ...layout,
                    usedArea,
                    wastedArea,
                    efficiency,
                    wasteWidth: this.sheetWidth - layout.usedWidth,
                    wasteHeight: this.sheetHeight - layout.usedHeight,
                };
            });

            layoutsWithMetrics.sort((a, b) => {
                if (b.totalBoxes !== a.totalBoxes) {
                    return b.totalBoxes - a.totalBoxes;
                }
                return b.efficiency - a.efficiency;
            });

            return layoutsWithMetrics;
        }
    }

    function renderResults(optimizer) {
        const layouts = optimizer.findOptimalLayout();

        if (layouts.length === 0) {
            return '<div class="co-summary"><h2>No valid layouts found</h2></div>';
        }

        const optimal = layouts[0];
        const maxBoxCount = optimal.totalBoxes;

        const optimalLayouts = layouts.filter(layout => layout.totalBoxes === maxBoxCount);
        const efficientLayouts = layouts.filter(layout =>
            layout.efficiency > 80 && layout.totalBoxes < maxBoxCount
        );

        let html = `
            <div class="co-summary">
                <h2><span class="dashicons dashicons-yes-alt"></span> Optimal Solution${optimalLayouts.length > 1 ? 's' : ''} Found</h2>
                <div class="co-summary-grid">
                    <div class="co-summary-item">
                        <label>Maximum Boxes</label>
                        <div class="value">${optimal.totalBoxes}</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Optimal Solutions</label>
                        <div class="value">${optimalLayouts.length}</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Best Efficiency</label>
                        <div class="value">${optimal.efficiency.toFixed(2)}%</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Used Area</label>
                        <div class="value">${optimal.usedArea.toFixed(0)} mm²</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Wasted Area</label>
                        <div class="value">${optimal.wastedArea.toFixed(0)} mm²</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Layout Type</label>
                        <div class="value">${optimal.layoutType === 'vertical' ? 'Vertical' : 'Horizontal'} Strips</div>
                    </div>
                </div>
            </div>
        `;

        if (optimalLayouts.length > 0) {
            html += `
                <div class="co-optimal-badge">
                    <span class="dashicons dashicons-star-filled"></span>
                    ${optimalLayouts.length} Optimal Solution${optimalLayouts.length > 1 ? 's' : ''} (${maxBoxCount} boxes each)
                </div>
            `;

            html += renderVisualDiagram(optimalLayouts[0], optimizer, 0);
            html += `<div class="co-layouts"><h3>Optimal Solutions (${maxBoxCount} boxes)</h3>`;

            optimalLayouts.forEach((layout, index) => {
                const layoutIndex = layouts.indexOf(layout);

                html += `
                    <div class="co-layout-item optimal" data-layout-index="${layoutIndex}">
                        <div class="co-layout-header">
                            <div class="co-layout-title">
                                <span class="dashicons dashicons-star-filled" style="color: #46b450;"></span>
                                ${layout.name} (Config ${index + 1})
                            </div>
                            <div class="co-layout-boxes">${layout.totalBoxes} boxes</div>
                        </div>
                        
                        <div class="co-layout-stats">
                            <div class="co-stat">
                                <label>Used Area</label>
                                <div class="value">${layout.usedArea.toFixed(2)} mm²</div>
                            </div>
                            <div class="co-stat">
                                <label>Wasted Area</label>
                                <div class="value">${layout.wastedArea.toFixed(2)} mm²</div>
                            </div>
                            <div class="co-stat">
                                <label>Efficiency</label>
                                <div class="value">${layout.efficiency.toFixed(2)}%</div>
                            </div>
                        </div>
                        
                        <div class="co-efficiency-bar">
                            <label>Material Efficiency</label>
                            <div class="co-efficiency-track">
                                <div class="co-efficiency-fill" style="width: ${layout.efficiency}%">
                                    ${layout.efficiency.toFixed(1)}%
                                </div>
                            </div>
                        </div>
                        
                        <div class="co-layout-details">
                            <p><strong>Layout Type:</strong> ${layout.layoutType === 'vertical' ? 'Vertical Strips' : 'Horizontal Strips'}</p>
                            ${layout.mainBoxes > 0 ? `<p><strong>Main Boxes:</strong> ${layout.mainBoxes} boxes (${layout.numStrips} strips × ${layout.boxesPerStrip} boxes per strip) - Orientation: ${layout.mainOrientation}</p>` : ''}
                            ${layout.remainingDetails && layout.remainingDetails.length > 0 ? `
                                <p><strong>Additional Boxes in Remaining Space:</strong></p>
                                <ul style="margin: 5px 0; padding-left: 20px;">
                                    ${layout.remainingDetails.map(detail =>
                    `<li>${detail.boxes} boxes (${detail.strips} strips × ${detail.boxesPerStrip} boxes) - ${detail.orientation} ${detail.isRotated ? '(rotated)' : ''}</li>`
                ).join('')}
                                </ul>
                            ` : ''}
                            <p><strong>Used Dimensions:</strong> ${layout.usedWidth.toFixed(1)} × ${layout.usedHeight.toFixed(1)} mm</p>
                            <p><strong>Waste:</strong> ${layout.wasteWidth.toFixed(1)} mm (width) × ${layout.wasteHeight.toFixed(1)} mm (height)</p>
                        </div>
                    </div>
                `;
            });

            html += "</div>";
        }

        if (efficientLayouts.length > 0) {
            html += `<div class="co-layouts"><h3>Other Efficient Options (>80% Efficiency)</h3>`;
            html += `<p style="color: #666; margin-bottom: 20px;">Showing ${efficientLayouts.length} additional efficient layouts</p>`;

            efficientLayouts.slice(0, 10).forEach((layout, index) => {
                const layoutIndex = layouts.indexOf(layout);

                html += `
                    <div class="co-layout-item" data-layout-index="${layoutIndex}">
                        <div class="co-layout-header">
                            <div class="co-layout-title">
                                ${layout.name}
                            </div>
                            <div class="co-layout-boxes">${layout.totalBoxes} boxes</div>
                        </div>
                        
                        <div class="co-layout-stats">
                            <div class="co-stat">
                                <label>Used Area</label>
                                <div class="value">${layout.usedArea.toFixed(2)} mm²</div>
                            </div>
                            <div class="co-stat">
                                <label>Wasted Area</label>
                                <div class="value">${layout.wastedArea.toFixed(2)} mm²</div>
                            </div>
                            <div class="co-stat">
                                <label>Efficiency</label>
                                <div class="value">${layout.efficiency.toFixed(2)}%</div>
                            </div>
                        </div>
                        
                        <div class="co-efficiency-bar">
                            <label>Material Efficiency</label>
                            <div class="co-efficiency-track">
                                <div class="co-efficiency-fill" style="width: ${layout.efficiency}%">
                                    ${layout.efficiency.toFixed(1)}%
                                </div>
                            </div>
                        </div>
                        
                        <div class="co-layout-details">
                            <p><strong>Layout Type:</strong> ${layout.layoutType === 'vertical' ? 'Vertical Strips' : 'Horizontal Strips'}</p>
                            ${layout.mainBoxes > 0 ? `<p><strong>Main Boxes:</strong> ${layout.mainBoxes} boxes (${layout.numStrips} strips × ${layout.boxesPerStrip} boxes per strip) - Orientation: ${layout.mainOrientation}</p>` : ''}
                            ${layout.rotatedBoxes > 0 ? `<p><strong>Additional Boxes:</strong> ${layout.rotatedBoxes} boxes</p>` : ''}
                            <p><strong>Used Dimensions:</strong> ${layout.usedWidth.toFixed(1)} × ${layout.usedHeight.toFixed(1)} mm</p>
                            <p><strong>Waste:</strong> ${layout.wasteWidth.toFixed(1)} mm (width) × ${layout.wasteHeight.toFixed(1)} mm (height)</p>
                        </div>
                    </div>
                `;
            });

            html += "</div>";
        }

        return html;
    }

    function renderVisualDiagram(layout, optimizer, layoutIndex) {
        // Simplified rendering - will need to be enhanced for complex recursive layouts
        let html = `
        <div class="co-visual-diagram" id="visual-diagram-${layoutIndex}">
            <h3><span class="dashicons dashicons-visibility"></span> Visual Layout Preview</h3>
            <div class="co-diagram-container">
                <p style="color: #666; padding: 20px;">
                    <strong>Layout Summary:</strong><br/>
                    Total Boxes: ${layout.totalBoxes}<br/>
                    Main Area: ${layout.mainBoxes} boxes<br/>
                    Additional Areas: ${layout.rotatedBoxes} boxes<br/>
                    <br/>
                    <em>Complex recursive layouts - detailed visualization coming soon</em>
                </p>
            </div>
        </div>
        `;

        return html;
    }

    let currentOptimizer = null;
    let currentLayouts = null;

    $(document).ready(function () {
        $("#calculate-btn").on("click", function () {
            const boxWidth = parseFloat($("#box-width").val());
            const boxHeight = parseFloat($("#box-height").val());
            const sheetWidth = parseFloat($("#sheet-width").val());
            const sheetHeight = parseFloat($("#sheet-height").val());
            const gap = parseFloat($("#gap").val());

            if (isNaN(boxWidth) || isNaN(boxHeight) || isNaN(sheetWidth) || isNaN(sheetHeight) || isNaN(gap)) {
                alert("Please enter valid numbers for all fields");
                return;
            }

            if (boxWidth <= 0 || boxHeight <= 0 || sheetWidth <= 0 || sheetHeight <= 0 || gap < 0) {
                alert("All dimensions must be positive numbers");
                return;
            }

            $("#loading").show();
            $("#results").hide();

            setTimeout(function () {
                currentOptimizer = new CuttingOptimizer(boxWidth, boxHeight, sheetWidth, sheetHeight, gap);
                currentLayouts = currentOptimizer.findOptimalLayout();
                const resultsHtml = renderResults(currentOptimizer);

                $("#results").html(resultsHtml).fadeIn();
                $("#loading").hide();

                $(".co-layout-item").on("click", function () {
                    const layoutIndex = $(this).data("layout-index");
                    const selectedLayout = currentLayouts[layoutIndex];

                    const newDiagram = renderVisualDiagram(selectedLayout, currentOptimizer, layoutIndex);
                    $(".co-visual-diagram").replaceWith(newDiagram);

                    $("html, body").animate({
                        scrollTop: $(".co-visual-diagram").offset().top - 100,
                    }, 500);

                    $(".co-layout-item").removeClass("selected");
                    $(this).addClass("selected");
                });
            }, 500);
        });

        $(".co-input-group input").on("keypress", function (e) {
            if (e.which === 13) {
                $("#calculate-btn").click();
            }
        });
    });
})(jQuery);