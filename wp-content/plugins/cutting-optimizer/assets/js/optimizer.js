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
                </div>
            </div>
            
            <div class="co-optimal-badge">
                <span class="dashicons dashicons-star-filled"></span>
                Recommended: ${optimal.name}
            </div>
        `;

    html += '<div class="co-layouts"><h3>All Layout Options</h3>';

    layouts.forEach((layout, index) => {
      const isOptimal = index === 0;

      html += `
                <div class="co-layout-item ${isOptimal ? "optimal" : ""}">
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
                    <p><strong>Horizontal Strips:</strong> ${layout.horizontalStrips} strips × ${layout.boxesPerHStrip} boxes (${optimizer.boxWidth}×${optimizer.boxHeight}) = ${layout.boxesInHorizontal} boxes</p>
                    <p><strong>Vertical Strips:</strong> ${layout.verticalStrips} strips × ${layout.boxesPerVStrip} boxes (${optimizer.boxHeight}×${optimizer.boxWidth}) = ${layout.boxesInVertical} boxes</p>
                `;
      }

      html += `
                    </div>
                </div>
            `;
    });

    html += "</div>";

    // Add visual diagram for optimal layout
    html += renderVisualDiagram(optimal, optimizer);

    return html;
  }

  function renderVisualDiagram(layout, optimizer) {
    let html = `
        <div class="co-visual-diagram">
            <h3><span class="dashicons dashicons-visibility"></span> Visual Layout (Optimal)</h3>
            <div class="co-diagram-container">
    `;

    if (layout.type === "single") {
      const scale = Math.min(
        600 / optimizer.sheetWidth,
        400 / optimizer.sheetHeight
      );
      const displayWidth = optimizer.sheetWidth * scale;
      const displayHeight = optimizer.sheetHeight * scale;
      const boxDisplayWidth = layout.boxWidth * scale;
      const boxDisplayHeight = layout.boxHeight * scale;

      html += `
            <div class="co-sheet" style="width: ${displayWidth}px; height: ${displayHeight}px;">
                <div class="co-sheet-label">${optimizer.sheetWidth} × ${
        optimizer.sheetHeight
      } mm</div>
                <div class="co-box-grid" style="grid-template-columns: repeat(${
                  layout.cols
                }, ${boxDisplayWidth}px); gap: ${optimizer.gap * scale}px;">
        `;

      for (let i = 0; i < layout.totalBoxes; i++) {
        html += `
                <div class="co-box" style="height: ${boxDisplayHeight}px;">
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
      const scale = Math.min(
        600 / optimizer.sheetWidth,
        400 / optimizer.sheetHeight
      );
      const displayWidth = optimizer.sheetWidth * scale;
      const displayHeight = optimizer.sheetHeight * scale;

      html += `
            <div class="co-sheet" style="width: ${displayWidth}px; min-height: ${displayHeight}px; padding: 10px;">
                <div class="co-sheet-label">${optimizer.sheetWidth} × ${
        optimizer.sheetHeight
      } mm</div>
                <div style="display: flex; flex-direction: column; gap: ${
                  optimizer.gap * scale
                }px;">
        `;

      let boxCounter = 1;

      // Render horizontal strips (boxes oriented as boxWidth × boxHeight)
      for (let h = 0; h < layout.horizontalStrips; h++) {
        const boxDisplayWidth = optimizer.boxWidth * scale;
        const boxDisplayHeight = optimizer.boxHeight * scale;

        html += `
                <div style="display: flex; gap: ${
                  optimizer.gap * scale
                }px; align-items: center;">
                    <span style="font-size: 10px; color: #646970; margin-right: 5px; min-width: 15px;">H:</span>
            `;

        for (let b = 0; b < layout.boxesPerHStrip; b++) {
          html += `
                    <div class="co-box" style="width: ${boxDisplayWidth}px; height: ${boxDisplayHeight}px; flex-shrink: 0;">
                        <span class="co-box-number">#${boxCounter++}</span>
                        <div style="font-size: 9px; margin-top: 2px;">${
                          optimizer.boxWidth
                        }×${optimizer.boxHeight}</div>
                    </div>
                `;
        }

        html += `</div>`;
      }

      // Render vertical strips (boxes oriented as boxHeight × boxWidth)
      for (let v = 0; v < layout.verticalStrips; v++) {
        const boxDisplayWidth = optimizer.boxHeight * scale;
        const boxDisplayHeight = optimizer.boxWidth * scale;

        html += `
                <div style="display: flex; gap: ${
                  optimizer.gap * scale
                }px; align-items: center;">
                    <span style="font-size: 10px; color: #646970; margin-right: 5px; min-width: 15px;">V:</span>
            `;

        for (let b = 0; b < layout.boxesPerVStrip; b++) {
          html += `
                    <div class="co-box" style="width: ${boxDisplayWidth}px; height: ${boxDisplayHeight}px; flex-shrink: 0;">
                        <span class="co-box-number">#${boxCounter++}</span>
                        <div style="font-size: 9px; margin-top: 2px;">${
                          optimizer.boxHeight
                        }×${optimizer.boxWidth}</div>
                    </div>
                `;
        }

        html += `</div>`;
      }

      html += `
                </div>
            </div>
            <div class="co-waste-info">
                <p><strong>Layout Type:</strong> Mixed Orientation</p>
                <p><strong>H:</strong> Horizontal strips with boxes ${optimizer.boxWidth}×${optimizer.boxHeight} mm</p>
                <p><strong>V:</strong> Vertical strips with boxes ${optimizer.boxHeight}×${optimizer.boxWidth} mm (rotated 90°)</p>
            </div>
        `;
    }

    html += `
            </div>
        </div>
    `;

    return html;
  }

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
        const optimizer = new CuttingOptimizer(
          boxWidth,
          boxHeight,
          sheetWidth,
          sheetHeight,
          gap
        );
        const resultsHtml = renderResults(optimizer);

        $("#results").html(resultsHtml).fadeIn();
        $("#loading").hide();
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
