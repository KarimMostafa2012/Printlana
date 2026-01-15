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
                for (let numStrips = 1; numStrips <= maxStrips; numStrips++) {
                    const usedWidth = numStrips * effectiveBoxW - this.gap;
                    const usedHeight = boxesPerStrip * effectiveBoxH - this.gap;
                    const mainBoxes = numStrips * boxesPerStrip;

                    const remainingWidth = this.sheetWidth - usedWidth - this.gap;
                    const remainingHeight = this.sheetHeight; // FIX: Use full sheet height!

                    // Try to fit rotated boxes in remaining space
                    let rotatedBoxes = 0;
                    let rotatedStrips = 0;
                    let rotatedBoxesPerStrip = 0;
                    let rotatedUsedWidth = 0;

                    if (remainingWidth >= effectiveBoxH) {
                        rotatedStrips = Math.floor(remainingWidth / effectiveBoxH);
                        rotatedBoxesPerStrip = Math.floor(remainingHeight / effectiveBoxW);
                        rotatedBoxes = rotatedStrips * rotatedBoxesPerStrip;
                        rotatedUsedWidth = rotatedStrips * effectiveBoxH - (rotatedStrips > 0 ? this.gap : 0);
                    }

                    const totalBoxes = mainBoxes + rotatedBoxes;
                    const totalUsedWidth = usedWidth + (rotatedStrips > 0 ? this.gap : 0) + rotatedUsedWidth;
                    const rotatedUsedHeight = rotatedBoxesPerStrip * effectiveBoxW - (rotatedBoxesPerStrip > 0 ? this.gap : 0);

                    allResults.push({
                        numStrips,
                        boxesPerStrip,
                        mainBoxes,
                        rotatedStrips,
                        rotatedBoxesPerStrip,
                        rotatedBoxes,
                        totalBoxes,
                        usedWidth: totalUsedWidth,
                        usedHeight: Math.max(usedHeight, rotatedUsedHeight),
                        mainOrientation: `${boxW}×${boxH}`,
                        rotatedOrientation: `${boxH}×${boxW}`,
                        layoutType: 'vertical',
                    });
                }
            } else {
                // Horizontal strips: boxes oriented as boxW × boxH
                const maxStrips = Math.floor(this.sheetHeight / effectiveBoxH);
                const boxesPerStrip = Math.floor(this.sheetWidth / effectiveBoxW);

                // Try different numbers of strips
                for (let numStrips = 1; numStrips <= maxStrips; numStrips++) {
                    const usedWidth = boxesPerStrip * effectiveBoxW - this.gap;
                    const usedHeight = numStrips * effectiveBoxH - this.gap;
                    const mainBoxes = numStrips * boxesPerStrip;

                    const remainingWidth = this.sheetWidth; // FIX: Use full sheet width!
                    const remainingHeight = this.sheetHeight - usedHeight - this.gap;

                    // Try to fit rotated boxes in remaining space
                    let rotatedBoxes = 0;
                    let rotatedStrips = 0;
                    let rotatedBoxesPerStrip = 0;
                    let rotatedUsedHeight = 0;

                    if (remainingHeight >= effectiveBoxW) {
                        rotatedStrips = Math.floor(remainingHeight / effectiveBoxW);
                        rotatedBoxesPerStrip = Math.floor(remainingWidth / effectiveBoxH);
                        rotatedBoxes = rotatedStrips * rotatedBoxesPerStrip;
                        rotatedUsedHeight = rotatedStrips * effectiveBoxW - (rotatedStrips > 0 ? this.gap : 0);
                    }

                    const totalBoxes = mainBoxes + rotatedBoxes;
                    const totalUsedHeight = usedHeight + (rotatedStrips > 0 ? this.gap : 0) + rotatedUsedHeight;
                    const rotatedUsedWidth = rotatedBoxesPerStrip * effectiveBoxH - (rotatedBoxesPerStrip > 0 ? this.gap : 0);

                    allResults.push({
                        numStrips,
                        boxesPerStrip,
                        mainBoxes,
                        rotatedStrips,
                        rotatedBoxesPerStrip,
                        rotatedBoxes,
                        totalBoxes,
                        usedWidth: Math.max(usedWidth, rotatedUsedWidth),
                        usedHeight: totalUsedHeight,
                        mainOrientation: `${boxW}×${boxH}`,
                        rotatedOrientation: `${boxH}×${boxW}`,
                        layoutType: 'horizontal',
                    });
                }
            }

            // Return ALL results (not just the best one)
            return allResults;
        }

        calculateAllLayouts() {
            const layouts = [];

            // Combination 1: Original box orientation (boxWidth × boxHeight)
            // Option A: Vertical strips
            const layout1A = this.calculateStripLayout(this.boxWidth, this.boxHeight, true);
            layout1A.forEach(layout => {
                layouts.push({
                    name: `Box ${this.boxWidth}×${this.boxHeight} - Vertical Strips`,
                    boxWidth: this.boxWidth,
                    boxHeight: this.boxHeight,
                    ...layout,
                });
            });

            // Option B: Horizontal strips
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
            // Option A: Vertical strips
            const layout2A = this.calculateStripLayout(this.boxHeight, this.boxWidth, true);
            layout2A.forEach(layout => {
                layouts.push({
                    name: `Box ${this.boxHeight}×${this.boxWidth} - Vertical Strips`,
                    boxWidth: this.boxHeight,
                    boxHeight: this.boxWidth,
                    ...layout,
                });
            });

            // Option B: Horizontal strips
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

        // Find ALL optimal solutions (same box count as the best)
        const optimalLayouts = layouts.filter(layout => layout.totalBoxes === maxBoxCount);

        // Filter remaining layouts with efficiency > 70%
        const efficientLayouts = layouts.filter(layout =>
            layout.efficiency > 70 && layout.totalBoxes < maxBoxCount
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

        // Show ALL optimal layouts
        if (optimalLayouts.length > 0) {
            html += `
                <div class="co-optimal-badge">
                    <span class="dashicons dashicons-star-filled"></span>
                    ${optimalLayouts.length} Optimal Solution${optimalLayouts.length > 1 ? 's' : ''} (${maxBoxCount} boxes each)
                </div>
            `;

            // Add visual diagram for FIRST optimal layout
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
                            <p><strong>Main Boxes:</strong> ${layout.mainBoxes} boxes (${layout.numStrips} strips × ${layout.boxesPerStrip} boxes per strip) - Orientation: ${layout.mainOrientation}</p>
                            ${layout.rotatedBoxes > 0 ? `<p><strong>Rotated Boxes:</strong> ${layout.rotatedBoxes} boxes (${layout.rotatedStrips} strips × ${layout.rotatedBoxesPerStrip} boxes per strip) - Orientation: ${layout.rotatedOrientation}</p>` : ''}
                            <p><strong>Used Dimensions:</strong> ${layout.usedWidth.toFixed(1)} × ${layout.usedHeight.toFixed(1)} mm</p>
                            <p><strong>Waste:</strong> ${layout.wasteWidth.toFixed(1)} mm (width) × ${layout.wasteHeight.toFixed(1)} mm (height)</p>
                        </div>
                    </div>
                `;
            });

            html += "</div>";
        }

        // Show other efficient layouts
        if (efficientLayouts.length > 0) {
            html += `<div class="co-layouts"><h3>Other Efficient Options (>70% Efficiency)</h3>`;
            html += `<p style="color: #666; margin-bottom: 20px;">Showing ${efficientLayouts.length} additional efficient layouts</p>`;

            efficientLayouts.forEach((layout, index) => {
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
                            <p><strong>Main Boxes:</strong> ${layout.mainBoxes} boxes (${layout.numStrips} strips × ${layout.boxesPerStrip} boxes per strip) - Orientation: ${layout.mainOrientation}</p>
                            ${layout.rotatedBoxes > 0 ? `<p><strong>Rotated Boxes:</strong> ${layout.rotatedBoxes} boxes (${layout.rotatedStrips} strips × ${layout.rotatedBoxesPerStrip} boxes per strip) - Orientation: ${layout.rotatedOrientation}</p>` : ''}
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
        let html = `
        <div class="co-visual-diagram" id="visual-diagram-${layoutIndex}">
            <h3><span class="dashicons dashicons-visibility"></span> Visual Layout${layoutIndex === 0 ? " (Optimal)" : ""}</h3>
            <div class="co-diagram-container">
        `;

        const actualSheetWidth = optimizer.sheetWidth;
        const actualSheetHeight = optimizer.sheetHeight;
        const totalUsedWidth = layout.usedWidth;
        const totalUsedHeight = layout.usedHeight;
        const usedWidthPercent = (totalUsedWidth / actualSheetWidth) * 100;
        const usedHeightPercent = (totalUsedHeight / actualSheetHeight) * 100;

        html += `
            <div class="co-sheet" style="width: 100%; aspect-ratio: ${actualSheetWidth} / ${actualSheetHeight};">
                <div class="co-sheet-label-width">${actualSheetWidth} cm</div>
                <div class="co-sheet-label-width-left-line"></div>
                <div class="co-sheet-label-width-right-line"></div>
                <div class="co-sheet-label-height">${actualSheetHeight}<br/>cm</div>
                <div class="co-sheet-label-height-bottom-line"></div>
                <div class="co-sheet-label-height-top-line"></div>
                <div style="display: flex; flex-direction: ${layout.layoutType === 'vertical' ? 'row' : 'column'}; width: ${usedWidthPercent}%; height: ${usedHeightPercent}%; padding: 10px; box-sizing: border-box; gap: ${optimizer.gap}px;">
        `;

        let boxCounter = 1;

        if (layout.layoutType === 'vertical') {
            // Render main vertical strips
            const stripWidth = (layout.boxWidth / totalUsedWidth) * 100;
            const boxHeight = (layout.boxHeight / totalUsedHeight) * 100;
            const gapPercent = (optimizer.gap / totalUsedWidth) * 100;

            for (let s = 0; s < layout.numStrips; s++) {
                html += `<div style="display: flex; flex-direction: column; width: ${stripWidth}%; height: 100%; gap: ${(optimizer.gap / totalUsedHeight) * 100}%;">`;

                for (let b = 0; b < layout.boxesPerStrip; b++) {
                    html += `
                        <div class="co-box" style="width: 100%; height: ${boxHeight}%; flex-shrink: 0;">
                            <span class="co-box-number">#${boxCounter++}</span>
                            <div style="font-size: 9px; margin-top: 2px;">${layout.boxWidth}×${layout.boxHeight}</div>
                        </div>
                    `;
                }

                html += `</div>`;
            }

            // Render rotated vertical strips
            if (layout.rotatedBoxes > 0) {
                const rotatedStripWidth = (layout.boxHeight / totalUsedWidth) * 100;
                const rotatedBoxHeight = (layout.boxWidth / totalUsedHeight) * 100;

                for (let s = 0; s < layout.rotatedStrips; s++) {
                    html += `<div style="display: flex; flex-direction: column; width: ${rotatedStripWidth}%; height: 100%; gap: ${(optimizer.gap / totalUsedHeight) * 100}%;">`;

                    for (let b = 0; b < layout.rotatedBoxesPerStrip; b++) {
                        html += `
                            <div class="co-box co-box-rotated" style="width: 100%; height: ${rotatedBoxHeight}%; flex-shrink: 0;">
                                <span class="co-box-number">#${boxCounter++}</span>
                                <div style="font-size: 9px; margin-top: 2px;">${layout.boxHeight}×${layout.boxWidth}</div>
                            </div>
                        `;
                    }

                    html += `</div>`;
                }
            }
        } else {
            // Render main horizontal strips
            const stripHeight = (layout.boxHeight / totalUsedHeight) * 100;
            const boxWidth = (layout.boxWidth / totalUsedWidth) * 100;
            const gapPercent = (optimizer.gap / totalUsedHeight) * 100;

            for (let s = 0; s < layout.numStrips; s++) {
                html += `<div style="display: flex; flex-direction: row; width: 100%; height: ${stripHeight}%; gap: ${(optimizer.gap / totalUsedWidth) * 100}%;">`;

                for (let b = 0; b < layout.boxesPerStrip; b++) {
                    html += `
                        <div class="co-box" style="width: ${boxWidth}%; height: 100%; flex-shrink: 0;">
                            <span class="co-box-number">#${boxCounter++}</span>
                            <div style="font-size: 9px; margin-top: 2px;">${layout.boxWidth}×${layout.boxHeight}</div>
                        </div>
                    `;
                }

                html += `</div>`;
            }

            // Render rotated horizontal strips
            if (layout.rotatedBoxes > 0) {
                const rotatedStripHeight = (layout.boxWidth / totalUsedHeight) * 100;
                const rotatedBoxWidth = (layout.boxHeight / totalUsedWidth) * 100;

                for (let s = 0; s < layout.rotatedStrips; s++) {
                    html += `<div style="display: flex; flex-direction: row; width: 100%; height: ${rotatedStripHeight}%; gap: ${(optimizer.gap / totalUsedWidth) * 100}%;">`;

                    for (let b = 0; b < layout.rotatedBoxesPerStrip; b++) {
                        html += `
                            <div class="co-box co-box-rotated" style="width: ${rotatedBoxWidth}%; height: 100%; flex-shrink: 0;">
                                <span class="co-box-number">#${boxCounter++}</span>
                                <div style="font-size: 9px; margin-top: 2px;">${layout.boxHeight}×${layout.boxWidth}</div>
                            </div>
                        `;
                    }

                    html += `</div>`;
                }
            }
        }

        html += `
                </div>
            </div>
            <div class="co-waste-info">
                <p><strong>Layout Type:</strong> ${layout.layoutType === 'vertical' ? 'Vertical Strips' : 'Horizontal Strips'}</p>
                <p><strong>Main Boxes:</strong> ${layout.mainBoxes} (${layout.mainOrientation})</p>
                ${layout.rotatedBoxes > 0 ? `<p><strong>Rotated Boxes:</strong> ${layout.rotatedBoxes} (${layout.rotatedOrientation})</p>` : ''}
                <p><strong>Waste Areas:</strong></p>
                <p>Right edge: ${layout.wasteWidth.toFixed(1)} mm</p>
                <p>Bottom edge: ${layout.wasteHeight.toFixed(1)} mm</p>
            </div>
        `;

        html += `
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