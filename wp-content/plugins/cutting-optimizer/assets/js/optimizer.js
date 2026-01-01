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

    calculateSingleOrientation(boxW, boxH, sheetW, sheetH) {
      const effectiveBoxWidth = boxW + this.gap;
      const effectiveBoxHeight = boxH + this.gap;

      const cols = Math.floor(sheetW / effectiveBoxWidth);
      const rows = Math.floor(sheetH / effectiveBoxHeight);
      const totalBoxes = cols * rows;

      const usedWidth = cols * effectiveBoxWidth - (cols > 0 ? this.gap : 0);
      const usedHeight = rows * effectiveBoxHeight - (rows > 0 ? this.gap : 0);

      return {
        cols,
        rows,
        totalBoxes,
        usedWidth,
        usedHeight,
        wasteWidth: sheetW - usedWidth,
        wasteHeight: sheetH - usedHeight,
        boxWidth: boxW,
        boxHeight: boxH,
      };
    }

    calculateMixedOrientation() {
      const results = [];

      const maxHorizontalStrips = Math.floor(
        this.sheetHeight / (this.boxHeight + this.gap)
      );

      for (let hStrips = 0; hStrips <= maxHorizontalStrips; hStrips++) {
        const horizontalHeight =
          hStrips > 0 ? hStrips * (this.boxHeight + this.gap) - this.gap : 0;
        const remainingHeight =
          this.sheetHeight - horizontalHeight - (hStrips > 0 ? this.gap : 0);

        if (remainingHeight < 0) continue;

        const vStrips = Math.floor(
          remainingHeight / (this.boxWidth + this.gap)
        );

        const boxesPerHStrip = Math.floor(
          this.sheetWidth / (this.boxWidth + this.gap)
        );
        const hBoxes = hStrips * boxesPerHStrip;

        const boxesPerVStrip = Math.floor(
          this.sheetWidth / (this.boxHeight + this.gap)
        );
        const vBoxes = vStrips * boxesPerVStrip;

        const totalBoxes = hBoxes + vBoxes;

        // Calculate used dimensions for mixed orientation
        const hUsedWidth =
          boxesPerHStrip > 0
            ? boxesPerHStrip * (this.boxWidth + this.gap) - this.gap
            : 0;
        const hUsedHeight =
          hStrips > 0 ? hStrips * (this.boxHeight + this.gap) - this.gap : 0;

        const vUsedWidth =
          boxesPerVStrip > 0
            ? boxesPerVStrip * (this.boxHeight + this.gap) - this.gap
            : 0;
        const vUsedHeight =
          vStrips > 0 ? vStrips * (this.boxWidth + this.gap) - this.gap : 0;

        const usedWidth = Math.max(hUsedWidth, vUsedWidth);
        const usedHeight =
          hUsedHeight + (vStrips > 0 ? this.gap : 0) + vUsedHeight;

        const wasteWidth = this.sheetWidth - usedWidth;
        const wasteHeight = this.sheetHeight - usedHeight;

        if (totalBoxes > 0) {
          results.push({
            type: "mixed",
            horizontalStrips: hStrips,
            verticalStrips: vStrips,
            boxesInHorizontal: hBoxes,
            boxesInVertical: vBoxes,
            totalBoxes,
            boxesPerHStrip,
            boxesPerVStrip,
            usedWidth,
            usedHeight,
            wasteWidth,
            wasteHeight,
            // Grid info for mixed layouts
            cols: `${boxesPerHStrip}H / ${boxesPerVStrip}V`,
            rows: `${hStrips}H / ${vStrips}V`,
          });
        }
      }

      return results;
    }

    calculateAllLayouts() {
      const layouts = [];

      const layout1 = this.calculateSingleOrientation(
        this.boxWidth,
        this.boxHeight,
        this.sheetWidth,
        this.sheetHeight
      );
      layouts.push({
        name: `Original Orientation (${this.boxWidth}×${this.boxHeight})`,
        type: "single",
        orientation: `${this.boxWidth}×${this.boxHeight}`,
        ...layout1,
      });

      const layout2 = this.calculateSingleOrientation(
        this.boxHeight,
        this.boxWidth,
        this.sheetWidth,
        this.sheetHeight
      );
      layouts.push({
        name: `Rotated Boxes (${this.boxHeight}×${this.boxWidth})`,
        type: "single",
        orientation: `${this.boxHeight}×${this.boxWidth}`,
        ...layout2,
      });

      const layout3 = this.calculateSingleOrientation(
        this.boxWidth,
        this.boxHeight,
        this.sheetHeight,
        this.sheetWidth
      );
      layouts.push({
        name: `Rotated Sheet (${this.sheetHeight}×${this.sheetWidth}) + Boxes (${this.boxWidth}×${this.boxHeight})`,
        type: "single",
        orientation: `${this.boxWidth}×${this.boxHeight}`,
        sheetRotated: true,
        ...layout3,
      });

      const layout4 = this.calculateSingleOrientation(
        this.boxHeight,
        this.boxWidth,
        this.sheetHeight,
        this.sheetWidth
      );
      layouts.push({
        name: `Rotated Sheet (${this.sheetHeight}×${this.sheetWidth}) + Rotated Boxes (${this.boxHeight}×${this.boxWidth})`,
        type: "single",
        orientation: `${this.boxHeight}×${this.boxWidth}`,
        sheetRotated: true,
        ...layout4,
      });

      const mixedLayouts = this.calculateMixedOrientation();
      mixedLayouts.forEach((mixed, index) => {
        const usedArea = mixed.totalBoxes * this.boxArea;
        const wastedArea = this.sheetArea - usedArea;
        const efficiency = (usedArea / this.sheetArea) * 100;

        layouts.push({
          name: `Mixed Orientation #${index + 1}`,
          type: "mixed",
          ...mixed,
          usedArea,
          wastedArea,
          efficiency,
        });
      });

      return layouts;
    }

    findOptimalLayout() {
      const layouts = this.calculateAllLayouts();

      const layoutsWithMetrics = layouts.map((layout) => {
        let usedArea, wastedArea, efficiency;

        if (layout.type === "single") {
          usedArea = layout.totalBoxes * this.boxArea;
          wastedArea = this.sheetArea - usedArea;
          efficiency = (usedArea / this.sheetArea) * 100;
        } else {
          usedArea = layout.usedArea;
          wastedArea = layout.wastedArea;
          efficiency = layout.efficiency;
        }

        return {
          ...layout,
          usedArea,
          wastedArea,
          efficiency,
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
    const optimal = layouts[0];

    // Find all layouts with the same efficiency and box count as optimal
    const optimalBoxCount = optimal.totalBoxes;
    const optimalEfficiency = optimal.efficiency.toFixed(2);

    let html = `
            <div class="co-summary">
                <h2><span class="dashicons dashicons-yes-alt"></span> Optimal Solution Found</h2>
                <div class="co-summary-grid">
                    <div class="co-summary-item">
                        <label>Maximum Boxes</label>
                        <div class="value">${optimal.totalBoxes}</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Efficiency</label>
                        <div class="value">${optimal.efficiency.toFixed(
                          2
                        )}%</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Used Area</label>
                        <div class="value">${optimal.usedArea.toFixed(
                          0
                        )} mm²</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Wasted Area</label>
                        <div class="value">${optimal.wastedArea.toFixed(
                          0
                        )} mm²</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Grid</label>
                        <div class="value">${optimal.cols} × ${
      optimal.rows
    }</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Used Dimensions</label>
                        <div class="value">${optimal.usedWidth.toFixed(
                          1
                        )} × ${optimal.usedHeight.toFixed(1)}</div>
                    </div>
                </div>
            </div>
            
            <div class="co-optimal-badge">
                <span class="dashicons dashicons-star-filled"></span>
                Recommended: ${optimal.name}
            </div>
        `;

    // Add visual diagram for optimal layout RIGHT AFTER summary
    html += renderVisualDiagram(optimal, optimizer, 0);

    html += '<div class="co-layouts"><h3>All Layout Options</h3>';

    layouts.forEach((layout, index) => {
      // Check if this layout is optimal (same box count and efficiency)
      const isOptimal =
        layout.totalBoxes === optimalBoxCount &&
        layout.efficiency.toFixed(2) === optimalEfficiency;

      html += `
                <div class="co-layout-item ${
                  isOptimal ? "optimal" : ""
                }" data-layout-index="${index}">
                    <div class="co-layout-header">
                        <div class="co-layout-title">
                            ${
                              isOptimal
                                ? '<span class="dashicons dashicons-star-filled" style="color: #46b450;"></span>'
                                : ""
                            }
                            ${layout.name}
                        </div>
                        <div class="co-layout-boxes">${
                          layout.totalBoxes
                        } boxes</div>
                    </div>
                    
                    <div class="co-layout-stats">
                        <div class="co-stat">
                            <label>Used Area</label>
                            <div class="value">${layout.usedArea.toFixed(
                              2
                            )} mm²</div>
                        </div>
                        <div class="co-stat">
                            <label>Wasted Area</label>
                            <div class="value">${layout.wastedArea.toFixed(
                              2
                            )} mm²</div>
                        </div>
                        <div class="co-stat">
                            <label>Efficiency</label>
                            <div class="value">${layout.efficiency.toFixed(
                              2
                            )}%</div>
                        </div>
                    </div>
                    
                    <div class="co-efficiency-bar">
                        <label>Material Efficiency</label>
                        <div class="co-efficiency-track">
                            <div class="co-efficiency-fill" style="width: ${
                              layout.efficiency
                            }%">
                                ${layout.efficiency.toFixed(1)}%
                            </div>
                        </div>
                    </div>
                    
                    <div class="co-layout-details">
            `;

      if (layout.type === "single") {
        html += `
                    <p><strong>Grid:</strong> ${layout.cols} columns × ${
          layout.rows
        } rows</p>
                    <p><strong>Box Dimensions:</strong> ${layout.boxWidth} × ${
          layout.boxHeight
        } mm</p>
                    <p><strong>Used Dimensions:</strong> ${layout.usedWidth.toFixed(
                      1
                    )} × ${layout.usedHeight.toFixed(1)} mm</p>
                    <p><strong>Waste:</strong> ${layout.wasteWidth.toFixed(
                      1
                    )} mm (width) × ${layout.wasteHeight.toFixed(
          1
        )} mm (height)</p>
                `;
      } else {
        html += `
                    <p><strong>Grid:</strong> ${layout.cols} columns, ${
          layout.rows
        } rows</p>
                    <p><strong>Box Dimensions:</strong> Horizontal (${
                      optimizer.boxWidth
                    }×${optimizer.boxHeight}), Vertical (${
          optimizer.boxHeight
        }×${optimizer.boxWidth})</p>
                    <p><strong>Used Dimensions:</strong> ${layout.usedWidth.toFixed(
                      1
                    )} × ${layout.usedHeight.toFixed(1)} mm</p>
                    <p><strong>Waste:</strong> ${layout.wasteWidth.toFixed(
                      1
                    )} mm (width) × ${layout.wasteHeight.toFixed(
          1
        )} mm (height)</p>
                    <p><strong>Horizontal Strips:</strong> ${
                      layout.horizontalStrips
                    } strips × ${layout.boxesPerHStrip} boxes = ${
          layout.boxesInHorizontal
        } boxes</p>
                    <p><strong>Vertical Strips:</strong> ${
                      layout.verticalStrips
                    } strips × ${layout.boxesPerVStrip} boxes = ${
          layout.boxesInVertical
        } boxes</p>
                `;
      }

      html += `
                    </div>
                </div>
            `;
    });

    html += "</div>";

    return html;
  }

  function renderVisualDiagram(layout, optimizer, layoutIndex) {
    let html = `
        <div class="co-visual-diagram" id="visual-diagram-${layoutIndex}">
            <h3><span class="dashicons dashicons-visibility"></span> Visual Layout${
              layoutIndex === 0 ? " (Optimal)" : ""
            }</h3>
            <div class="co-diagram-container">
    `;

    if (layout.type === "single") {
      // Calculate the total used dimensions
      const totalUsedWidth = layout.usedWidth;
      const totalUsedHeight = layout.usedHeight;

      // Calculate box dimensions as percentages of the USED space
      const usedWidthPercent = (totalUsedWidth / optimizer.sheetWidth) * 100;
      const usedHeightPercent = (totalUsedHeight / optimizer.sheetHeight) * 100;

      // Calculate individual box size as percentage of total used space
      const boxWidthPercent = (layout.boxWidth / totalUsedWidth) * 100;
      const boxHeightPercent = (layout.boxHeight / totalUsedHeight) * 100;

      // Calculate gap as percentage of total used space
      const gapWidthPercent = (optimizer.gap / totalUsedWidth) * 100;
      const gapHeightPercent = (optimizer.gap / totalUsedHeight) * 100;

      html += `
            <div class="co-sheet" style="width: 100%; aspect-ratio: ${optimizer.sheetWidth} / ${optimizer.sheetHeight};">
                <div class="co-sheet-label-width">${optimizer.sheetWidth} cm</div>
                <div class="co-sheet-label-width-left-line"></div>
                <div class="co-sheet-label-width-right-line"></div>
                <div class="co-sheet-label-height">${optimizer.sheetHeight}<br/>cm</div>
                <div class="co-sheet-label-height-bottom-line"></div>
                <div class="co-sheet-label-height-top-line"></div>
                <div class="co-box-grid" style="
                    grid-template-columns: repeat(${layout.cols}, ${boxWidthPercent}%);
                    grid-template-rows: repeat(${layout.rows}, ${boxHeightPercent}%);
                    gap: ${gapHeightPercent}% ${gapWidthPercent}%;
                    width: ${usedWidthPercent}%;
                    height: ${usedHeightPercent}%;
                ">
        `;

      for (let i = 0; i < layout.totalBoxes; i++) {
        html += `
                <div class="co-box" style="aspect-ratio: ${layout.boxWidth} / ${layout.boxHeight};">
                    <span class="co-box-number">#${i + 1}</span>
                </div>
            `;
      }

      html += `
                </div>
            </div>
            <div class="co-waste-info">
                <p><strong>Waste Areas:</strong></p>
                <p>Right edge: ${layout.wasteWidth.toFixed(1)} mm</p>
                <p>Bottom edge: ${layout.wasteHeight.toFixed(1)} mm</p>
            </div>
        `;
    } else {
      // Mixed orientation layout
      // Calculate total used dimensions as percentages of sheet
      const usedWidthPercent = (layout.usedWidth / optimizer.sheetWidth) * 100;
      const usedHeightPercent = (layout.usedHeight / optimizer.sheetHeight) * 100;

      // Calculate strip heights as percentages of USED height
      const hStripHeight = layout.horizontalStrips > 0
        ? (optimizer.boxHeight / layout.usedHeight) * 100
        : 0;
      const vStripHeight = layout.verticalStrips > 0
        ? (optimizer.boxWidth / layout.usedHeight) * 100
        : 0;

      // Calculate box widths as percentages of USED width
      const hBoxWidth = layout.boxesPerHStrip > 0
        ? (optimizer.boxWidth / layout.usedWidth) * 100
        : 0;
      const vBoxWidth = layout.boxesPerVStrip > 0
        ? (optimizer.boxHeight / layout.usedWidth) * 100
        : 0;

      // Calculate gap percentages
      const gapWidthPercent = (optimizer.gap / layout.usedWidth) * 100;
      const gapHeightPercent = (optimizer.gap / layout.usedHeight) * 100;

      html += `
            <div class="co-sheet" style="width: 100%; aspect-ratio: ${optimizer.sheetWidth} / ${optimizer.sheetHeight};">
                <div class="co-sheet-label-width">${optimizer.sheetWidth} cm</div>
                <div class="co-sheet-label-width-left-line"></div>
                <div class="co-sheet-label-width-right-line"></div>
                <div class="co-sheet-label-height">${optimizer.sheetHeight}<br/>cm</div>
                <div class="co-sheet-label-height-bottom-line"></div>
                <div class="co-sheet-label-height-top-line"></div>
                <div style="display: flex; flex-direction: column; width: ${usedWidthPercent}%; height: ${usedHeightPercent}%; padding: 10px; box-sizing: border-box;">
        `;

      let boxCounter = 1;

      // Render horizontal strips (boxes oriented as boxWidth × boxHeight)
      for (let h = 0; h < layout.horizontalStrips; h++) {
        html += `
                <div style="display: flex; align-items: center; height: ${hStripHeight}%; ${h < layout.horizontalStrips - 1 || layout.verticalStrips > 0 ? `margin-bottom: ${gapHeightPercent}%;` : ''}">
                    <div style="display: flex; gap: ${gapWidthPercent}%; width: 100%; height: 100%;">
            `;

        for (let b = 0; b < layout.boxesPerHStrip; b++) {
          html += `
                    <div class="co-box" style="width: ${hBoxWidth}%; height: 100%; flex-shrink: 0;">
                        <span class="co-box-number">#${boxCounter++}</span>
                        <div style="font-size: 9px; margin-top: 2px;">${
                          optimizer.boxWidth
                        }×${optimizer.boxHeight}</div>
                    </div>
                `;
        }

        html += `
                    </div>
                </div>
            `;
      }

      // Render vertical strips (boxes oriented as boxHeight × boxWidth)
      for (let v = 0; v < layout.verticalStrips; v++) {
        html += `
                <div style="display: flex; align-items: center; height: ${vStripHeight}%; ${v < layout.verticalStrips - 1 ? `margin-bottom: ${gapHeightPercent}%;` : ''}">
                    <div style="display: flex; gap: ${gapWidthPercent}%; width: 100%; height: 100%;">
            `;

        for (let b = 0; b < layout.boxesPerVStrip; b++) {
          html += `
                    <div class="co-box" style="width: ${vBoxWidth}%; height: 100%; flex-shrink: 0;">
                        <span class="co-box-number">#${boxCounter++}</span>
                        <div style="font-size: 9px; margin-top: 2px;">${
                          optimizer.boxHeight
                        }×${optimizer.boxWidth}</div>
                    </div>
                `;
        }

        html += `
                    </div>
                </div>
            `;
      }

      html += `
                </div>
            </div>
            <div class="co-waste-info">
                <p><strong>Layout Type:</strong> Mixed Orientation</p>
                <p><strong>Waste Areas:</strong></p>
                <p>Right edge: ${layout.wasteWidth.toFixed(1)} mm</p>
                <p>Bottom edge: ${layout.wasteHeight.toFixed(1)} mm</p>
            </div>
        `;
    }

    html += `
            </div>
        </div>
    `;

    return html;
  }

  // Store optimizer instance globally for click handlers
  let currentOptimizer = null;
  let currentLayouts = null;

  $(document).ready(function () {
    $("#calculate-btn").on("click", function () {
      const boxWidth = parseFloat($("#box-width").val());
      const boxHeight = parseFloat($("#box-height").val());
      const sheetWidth = parseFloat($("#sheet-width").val());
      const sheetHeight = parseFloat($("#sheet-height").val());
      const gap = parseFloat($("#gap").val());

      if (
        isNaN(boxWidth) ||
        isNaN(boxHeight) ||
        isNaN(sheetWidth) ||
        isNaN(sheetHeight) ||
        isNaN(gap)
      ) {
        alert("Please enter valid numbers for all fields");
        return;
      }

      if (
        boxWidth <= 0 ||
        boxHeight <= 0 ||
        sheetWidth <= 0 ||
        sheetHeight <= 0 ||
        gap < 0
      ) {
        alert("All dimensions must be positive numbers");
        return;
      }

      $("#loading").show();
      $("#results").hide();

      setTimeout(function () {
        currentOptimizer = new CuttingOptimizer(
          boxWidth,
          boxHeight,
          sheetWidth,
          sheetHeight,
          gap
        );
        currentLayouts = currentOptimizer.findOptimalLayout();
        const resultsHtml = renderResults(currentOptimizer);

        $("#results").html(resultsHtml).fadeIn();
        $("#loading").hide();

        // Attach click handlers to layout items
        $(".co-layout-item").on("click", function () {
          const layoutIndex = $(this).data("layout-index");
          const selectedLayout = currentLayouts[layoutIndex];

          // Update visual diagram
          const newDiagram = renderVisualDiagram(
            selectedLayout,
            currentOptimizer,
            layoutIndex
          );
          $(".co-visual-diagram").replaceWith(newDiagram);

          // Scroll to visual diagram
          $("html, body").animate(
            {
              scrollTop: $(".co-visual-diagram").offset().top - 100,
            },
            500
          );

          // Highlight selected layout item
          $(".co-layout-item").removeClass("selected");
          $(this).addClass("selected");
        });
      }, 500);
    });

    // Trigger calculation on Enter key
    $(".co-input-group input").on("keypress", function (e) {
      if (e.which === 13) {
        $("#calculate-btn").click();
      }
    });
  });
})(jQuery);
