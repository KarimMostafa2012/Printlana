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
                        cols: cols1,
                        rows: rows1,
                        orientation: `${boxW}×${boxH}`,
                        isRotated: false,
                        usedWidth: usedW1,
                        usedHeight: usedH1,
                        boxWidth: boxW,
                        boxHeight: boxH,
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
                        cols: cols2,
                        rows: rows2,
                        orientation: `${boxH}×${boxW}`,
                        isRotated: true,
                        usedWidth: usedW2,
                        usedHeight: usedH2,
                        boxWidth: boxH,
                        boxHeight: boxW,
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
                        cols: numStrips,
                        rows: boxesPerStrip,
                        boxWidth: boxW,
                        boxHeight: boxH,
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
                        cols: boxesPerStrip,
                        rows: numStrips,
                        boxWidth: boxW,
                        boxHeight: boxH,
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
                    ...layout,
                });
            });

            const layout1B = this.calculateStripLayout(this.boxWidth, this.boxHeight, false);
            layout1B.forEach(layout => {
                layouts.push({
                    name: `Box ${this.boxWidth}×${this.boxHeight} - Horizontal Strips`,
                    ...layout,
                });
            });

            // Combination 2: Rotated box orientation (boxHeight × boxWidth)
            const layout2A = this.calculateStripLayout(this.boxHeight, this.boxWidth, true);
            layout2A.forEach(layout => {
                layouts.push({
                    name: `Box ${this.boxHeight}×${this.boxWidth} - Vertical Strips`,
                    ...layout,
                });
            });

            const layout2B = this.calculateStripLayout(this.boxHeight, this.boxWidth, false);
            layout2B.forEach(layout => {
                layouts.push({
                    name: `Box ${this.boxHeight}×${this.boxWidth} - Horizontal Strips`,
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

    // Separate Lid Optimizer class
    class SeparateLidOptimizer {
        constructor(boxWidth, boxHeight, lidWidth, lidHeight, sheetWidth, sheetHeight, gap = 0.3) {
            this.boxWidth = boxWidth;
            this.boxHeight = boxHeight;
            this.lidWidth = lidWidth;
            this.lidHeight = lidHeight;
            this.sheetWidth = sheetWidth;
            this.sheetHeight = sheetHeight;
            this.gap = gap;
            this.boxArea = boxWidth * boxHeight;
            this.lidArea = lidWidth * lidHeight;
            this.sheetArea = sheetWidth * sheetHeight;
        }

        // Case 2: Separate sheets for boxes and lids
        calculateSeparateSheets() {
            const boxOptimizer = new CuttingOptimizer(
                this.boxWidth, this.boxHeight,
                this.sheetWidth, this.sheetHeight, this.gap
            );
            const lidOptimizer = new CuttingOptimizer(
                this.lidWidth, this.lidHeight,
                this.sheetWidth, this.sheetHeight, this.gap
            );

            const boxLayouts = boxOptimizer.findOptimalLayout();
            const lidLayouts = lidOptimizer.findOptimalLayout();

            if (boxLayouts.length === 0 || lidLayouts.length === 0) {
                return null;
            }

            const bestBoxLayout = boxLayouts[0];
            const bestLidLayout = lidLayouts[0];

            const maxBoxes = bestBoxLayout.totalBoxes;
            const maxLids = bestLidLayout.totalBoxes;
            const pairs = Math.min(maxBoxes, maxLids);

            const usedArea = pairs * this.boxArea + pairs * this.lidArea;
            const totalSheetArea = 2 * this.sheetArea;
            const efficiency = (usedArea / totalSheetArea) * 100;

            return {
                type: 'separate',
                pairs: pairs,
                maxBoxes: maxBoxes,
                maxLids: maxLids,
                boxLayout: bestBoxLayout,
                lidLayout: bestLidLayout,
                efficiency: efficiency,
                sheetsNeeded: 2,
                usedArea: usedArea,
                wastedArea: totalSheetArea - usedArea,
                boxOptimizer: boxOptimizer,
                lidOptimizer: lidOptimizer
            };
        }

        // Case 1: Combined sheet (boxes + lids on same sheet)
        calculateCombinedSheet() {
            let bestResult = null;

            // Try both split approaches and mixed placement
            const splitResults = this.trySplitApproach();
            const mixedResults = this.tryMixedPlacement();

            // Get best from split approach
            if (splitResults && (!bestResult || splitResults.pairs > bestResult.pairs ||
                (splitResults.pairs === bestResult.pairs && splitResults.efficiency > bestResult.efficiency))) {
                bestResult = splitResults;
            }

            // Get best from mixed placement
            if (mixedResults && (!bestResult || mixedResults.pairs > bestResult.pairs ||
                (mixedResults.pairs === bestResult.pairs && mixedResults.efficiency > bestResult.efficiency))) {
                bestResult = mixedResults;
            }

            return bestResult;
        }

        // Split sheet into two regions
        trySplitApproach() {
            let bestResult = null;

            // Try vertical splits
            for (let splitRatio = 0.1; splitRatio <= 0.9; splitRatio += 0.05) {
                const result = this.evaluateSplit('vertical', splitRatio);
                if (result && result.pairs > 0) {
                    if (!bestResult || result.pairs > bestResult.pairs ||
                        (result.pairs === bestResult.pairs && result.efficiency > bestResult.efficiency)) {
                        bestResult = result;
                    }
                }

                // Try swapped (lids in region1, boxes in region2)
                const resultSwapped = this.evaluateSplit('vertical', splitRatio, true);
                if (resultSwapped && resultSwapped.pairs > 0) {
                    if (!bestResult || resultSwapped.pairs > bestResult.pairs ||
                        (resultSwapped.pairs === bestResult.pairs && resultSwapped.efficiency > bestResult.efficiency)) {
                        bestResult = resultSwapped;
                    }
                }
            }

            // Try horizontal splits
            for (let splitRatio = 0.1; splitRatio <= 0.9; splitRatio += 0.05) {
                const result = this.evaluateSplit('horizontal', splitRatio);
                if (result && result.pairs > 0) {
                    if (!bestResult || result.pairs > bestResult.pairs ||
                        (result.pairs === bestResult.pairs && result.efficiency > bestResult.efficiency)) {
                        bestResult = result;
                    }
                }

                // Try swapped
                const resultSwapped = this.evaluateSplit('horizontal', splitRatio, true);
                if (resultSwapped && resultSwapped.pairs > 0) {
                    if (!bestResult || resultSwapped.pairs > bestResult.pairs ||
                        (resultSwapped.pairs === bestResult.pairs && resultSwapped.efficiency > bestResult.efficiency)) {
                        bestResult = resultSwapped;
                    }
                }
            }

            return bestResult;
        }

        evaluateSplit(splitType, splitRatio, swapped = false) {
            let region1Width, region1Height, region2Width, region2Height;
            let region2OffsetX = 0, region2OffsetY = 0;

            if (splitType === 'vertical') {
                region1Width = this.sheetWidth * splitRatio - this.gap / 2;
                region1Height = this.sheetHeight;
                region2Width = this.sheetWidth * (1 - splitRatio) - this.gap / 2;
                region2Height = this.sheetHeight;
                region2OffsetX = this.sheetWidth * splitRatio + this.gap / 2;
            } else {
                region1Width = this.sheetWidth;
                region1Height = this.sheetHeight * splitRatio - this.gap / 2;
                region2Width = this.sheetWidth;
                region2Height = this.sheetHeight * (1 - splitRatio) - this.gap / 2;
                region2OffsetY = this.sheetHeight * splitRatio + this.gap / 2;
            }

            // Determine what goes in each region
            const box1W = swapped ? this.lidWidth : this.boxWidth;
            const box1H = swapped ? this.lidHeight : this.boxHeight;
            const box2W = swapped ? this.boxWidth : this.lidWidth;
            const box2H = swapped ? this.boxHeight : this.lidHeight;

            // Calculate how many fit in each region
            const region1Optimizer = new CuttingOptimizer(box1W, box1H, region1Width, region1Height, this.gap);
            const region2Optimizer = new CuttingOptimizer(box2W, box2H, region2Width, region2Height, this.gap);

            const layouts1 = region1Optimizer.findOptimalLayout();
            const layouts2 = region2Optimizer.findOptimalLayout();

            if (layouts1.length === 0 || layouts2.length === 0) {
                return null;
            }

            const count1 = layouts1[0].totalBoxes;
            const count2 = layouts2[0].totalBoxes;

            // Pairs = minimum of both counts (since we need equal boxes and lids)
            const pairs = Math.min(count1, count2);

            if (pairs === 0) return null;

            const usedArea = pairs * this.boxArea + pairs * this.lidArea;
            const efficiency = (usedArea / this.sheetArea) * 100;

            return {
                type: 'combined',
                approach: 'split',
                splitType: splitType,
                splitRatio: splitRatio,
                swapped: swapped,
                pairs: pairs,
                boxCount: swapped ? count2 : count1,
                lidCount: swapped ? count1 : count2,
                boxLayout: swapped ? layouts2[0] : layouts1[0],
                lidLayout: swapped ? layouts1[0] : layouts2[0],
                region1: { width: region1Width, height: region1Height, offsetX: 0, offsetY: 0 },
                region2: { width: region2Width, height: region2Height, offsetX: region2OffsetX, offsetY: region2OffsetY },
                efficiency: efficiency,
                sheetsNeeded: 1,
                usedArea: usedArea,
                wastedArea: this.sheetArea - usedArea,
                boxOptimizer: swapped ? region2Optimizer : region1Optimizer,
                lidOptimizer: swapped ? region1Optimizer : region2Optimizer
            };
        }

        // Try mixed placement - fit lids in gaps after placing boxes
        tryMixedPlacement() {
            let bestResult = null;

            // Get all box layouts
            const boxOptimizer = new CuttingOptimizer(
                this.boxWidth, this.boxHeight,
                this.sheetWidth, this.sheetHeight, this.gap
            );
            const boxLayouts = boxOptimizer.findOptimalLayout();

            // Try different numbers of boxes and see how many lids fit
            for (const boxLayout of boxLayouts.slice(0, 20)) { // Check top layouts
                const boxCount = boxLayout.totalBoxes;
                if (boxCount === 0) continue;

                // Calculate remaining space after placing boxes
                const remaining = this.calculateRemainingSpaceForLids(boxLayout);

                if (remaining.maxLids > 0) {
                    const pairs = Math.min(boxCount, remaining.maxLids);
                    const usedArea = pairs * this.boxArea + pairs * this.lidArea;
                    const efficiency = (usedArea / this.sheetArea) * 100;

                    if (!bestResult || pairs > bestResult.pairs ||
                        (pairs === bestResult.pairs && efficiency > bestResult.efficiency)) {
                        bestResult = {
                            type: 'combined',
                            approach: 'mixed',
                            pairs: pairs,
                            boxCount: boxCount,
                            lidCount: remaining.maxLids,
                            boxLayout: boxLayout,
                            lidPlacements: remaining.placements,
                            efficiency: efficiency,
                            sheetsNeeded: 1,
                            usedArea: usedArea,
                            wastedArea: this.sheetArea - usedArea,
                            boxOptimizer: boxOptimizer
                        };
                    }
                }
            }

            return bestResult;
        }

        calculateRemainingSpaceForLids(boxLayout) {
            const placements = [];
            let maxLids = 0;

            // Calculate the area used by boxes
            const boxUsedWidth = boxLayout.usedWidth;
            const boxUsedHeight = boxLayout.usedHeight;

            // Try placing lids in right remaining space
            const rightWidth = this.sheetWidth - boxUsedWidth - this.gap;
            const rightHeight = this.sheetHeight;

            if (rightWidth >= Math.min(this.lidWidth, this.lidHeight)) {
                const rightOptimizer = new CuttingOptimizer(
                    this.lidWidth, this.lidHeight,
                    rightWidth, rightHeight, this.gap
                );
                const rightLayouts = rightOptimizer.findOptimalLayout();
                if (rightLayouts.length > 0 && rightLayouts[0].totalBoxes > 0) {
                    maxLids += rightLayouts[0].totalBoxes;
                    placements.push({
                        region: 'right',
                        offsetX: boxUsedWidth + this.gap,
                        offsetY: 0,
                        width: rightWidth,
                        height: rightHeight,
                        layout: rightLayouts[0],
                        count: rightLayouts[0].totalBoxes
                    });
                }
            }

            // Try placing lids in bottom remaining space
            const bottomWidth = boxUsedWidth;
            const bottomHeight = this.sheetHeight - boxUsedHeight - this.gap;

            if (bottomHeight >= Math.min(this.lidWidth, this.lidHeight)) {
                const bottomOptimizer = new CuttingOptimizer(
                    this.lidWidth, this.lidHeight,
                    bottomWidth, bottomHeight, this.gap
                );
                const bottomLayouts = bottomOptimizer.findOptimalLayout();
                if (bottomLayouts.length > 0 && bottomLayouts[0].totalBoxes > 0) {
                    maxLids += bottomLayouts[0].totalBoxes;
                    placements.push({
                        region: 'bottom',
                        offsetX: 0,
                        offsetY: boxUsedHeight + this.gap,
                        width: bottomWidth,
                        height: bottomHeight,
                        layout: bottomLayouts[0],
                        count: bottomLayouts[0].totalBoxes
                    });
                }
            }

            return { maxLids, placements };
        }

        // Get all valid combined configurations (for showing alternatives)
        getAllCombinedConfigurations() {
            const allConfigs = [];

            // Try all vertical splits
            for (let splitRatio = 0.1; splitRatio <= 0.9; splitRatio += 0.05) {
                const result = this.evaluateSplit('vertical', splitRatio);
                if (result && result.pairs > 0) {
                    allConfigs.push(result);
                }

                const resultSwapped = this.evaluateSplit('vertical', splitRatio, true);
                if (resultSwapped && resultSwapped.pairs > 0) {
                    allConfigs.push(resultSwapped);
                }
            }

            // Try all horizontal splits
            for (let splitRatio = 0.1; splitRatio <= 0.9; splitRatio += 0.05) {
                const result = this.evaluateSplit('horizontal', splitRatio);
                if (result && result.pairs > 0) {
                    allConfigs.push(result);
                }

                const resultSwapped = this.evaluateSplit('horizontal', splitRatio, true);
                if (resultSwapped && resultSwapped.pairs > 0) {
                    allConfigs.push(resultSwapped);
                }
            }

            // Try mixed placements
            const boxOptimizer = new CuttingOptimizer(
                this.boxWidth, this.boxHeight,
                this.sheetWidth, this.sheetHeight, this.gap
            );
            const boxLayouts = boxOptimizer.findOptimalLayout();

            for (const boxLayout of boxLayouts.slice(0, 20)) {
                const boxCount = boxLayout.totalBoxes;
                if (boxCount === 0) continue;

                const remaining = this.calculateRemainingSpaceForLids(boxLayout);

                if (remaining.maxLids > 0) {
                    const pairs = Math.min(boxCount, remaining.maxLids);
                    const usedArea = pairs * this.boxArea + pairs * this.lidArea;
                    const efficiency = (usedArea / this.sheetArea) * 100;

                    allConfigs.push({
                        type: 'combined',
                        approach: 'mixed',
                        pairs: pairs,
                        boxCount: boxCount,
                        lidCount: remaining.maxLids,
                        boxLayout: boxLayout,
                        lidPlacements: remaining.placements,
                        efficiency: efficiency,
                        sheetsNeeded: 1,
                        usedArea: usedArea,
                        wastedArea: this.sheetArea - usedArea,
                        boxOptimizer: boxOptimizer
                    });
                }
            }

            // Sort by pairs (descending), then efficiency (descending)
            allConfigs.sort((a, b) => {
                if (b.pairs !== a.pairs) return b.pairs - a.pairs;
                return b.efficiency - a.efficiency;
            });

            // Remove duplicates (same pairs and very similar efficiency)
            const uniqueConfigs = [];
            for (const config of allConfigs) {
                const isDuplicate = uniqueConfigs.some(c =>
                    c.pairs === config.pairs &&
                    Math.abs(c.efficiency - config.efficiency) < 0.1 &&
                    c.approach === config.approach &&
                    c.splitType === config.splitType
                );
                if (!isDuplicate) {
                    uniqueConfigs.push(config);
                }
            }

            return uniqueConfigs;
        }

        // Compare Case 1 and Case 2 and determine the best option
        findOptimalStrategy() {
            const separateResult = this.calculateSeparateSheets();
            const combinedResult = this.calculateCombinedSheet();
            const allCombinedConfigs = this.getAllCombinedConfigurations();

            let recommendation = null;
            let reason = '';

            // Decision logic
            if (!combinedResult && !separateResult) {
                return { error: 'No valid layouts found for boxes or lids' };
            }

            if (!combinedResult) {
                recommendation = 'separate';
                reason = 'Combined sheet layout not possible with these dimensions';
            } else if (!separateResult) {
                recommendation = 'combined';
                reason = 'Separate sheet layout not possible';
            } else {
                // Compare both options
                // Priority: 1. More pairs with same/fewer sheets
                //          2. Same pairs with fewer sheets
                //          3. Same pairs, same sheets -> higher efficiency

                const combinedPairs = combinedResult.pairs;
                const separatePairs = separateResult.pairs;

                if (combinedPairs > separatePairs) {
                    recommendation = 'combined';
                    reason = `Combined sheet yields ${combinedPairs} pairs vs ${separatePairs} pairs on separate sheets`;
                } else if (separatePairs > combinedPairs) {
                    recommendation = 'separate';
                    reason = `Separate sheets yield ${separatePairs} pairs vs ${combinedPairs} pairs on combined sheet`;
                } else {
                    // Same pairs - prefer combined (uses 1 sheet instead of 2)
                    recommendation = 'combined';
                    reason = `Same ${combinedPairs} pairs, but combined uses only 1 sheet vs 2 sheets`;
                }
            }

            return {
                combined: combinedResult,
                separate: separateResult,
                allCombinedConfigs: allCombinedConfigs,
                recommendation: recommendation,
                reason: reason
            };
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
                    `<li>${detail.boxes} boxes (${detail.cols} cols × ${detail.rows} rows) - ${detail.orientation} ${detail.isRotated ? '(rotated)' : ''}</li>`
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
    let html = `
    <div class="co-visual-diagram" id="visual-diagram-${layoutIndex}">
        <h3><span class="dashicons dashicons-visibility"></span> Visual Layout${layoutIndex === 0 ? " (Optimal)" : ""}</h3>
        <div class="co-diagram-container">
    `;

    const actualSheetWidth = optimizer.sheetWidth;
    const actualSheetHeight = optimizer.sheetHeight;

    html += `
        <div class="co-sheet" style="width: 100%; aspect-ratio: ${actualSheetWidth} / ${actualSheetHeight}; position: relative; border: 2px solid #333; background: #f9f9f9;">
            <div class="co-sheet-label-width">${actualSheetWidth} cm</div>
            <div class="co-sheet-label-width-left-line"></div>
            <div class="co-sheet-label-width-right-line"></div>
            <div class="co-sheet-label-height">${actualSheetHeight}<br/>cm</div>
            <div class="co-sheet-label-height-bottom-line"></div>
            <div class="co-sheet-label-height-top-line"></div>
    `;

    let boxCounter = 1;

    // Check if we have a simple layout (only main boxes, no recursive details)
    const isSimpleLayout = !layout.remainingDetails || layout.remainingDetails.length === 0;

    if (isSimpleLayout && layout.mainBoxes > 0) {
        // Simple grid layout - use CSS Grid
        const totalUsedWidth = layout.usedWidth;
        const totalUsedHeight = layout.usedHeight;
        const usedWidthPercent = (totalUsedWidth / actualSheetWidth) * 100;
        const usedHeightPercent = (totalUsedHeight / actualSheetHeight) * 100;
        const boxWidthPercent = (layout.boxWidth / totalUsedWidth) * 100;
        const boxHeightPercent = (layout.boxHeight / totalUsedHeight) * 100;
        const gapWidthPercent = (optimizer.gap / totalUsedWidth) * 100;
        const gapHeightPercent = (optimizer.gap / totalUsedHeight) * 100;

        html += `
            <div class="co-box-grid" style="
                position: absolute;
                top: 0;
                left: 0;
                display: grid;
                grid-template-columns: repeat(${layout.cols}, ${boxWidthPercent}%);
                grid-template-rows: repeat(${layout.rows}, ${boxHeightPercent}%);
                gap: ${gapHeightPercent}% ${gapWidthPercent}%;
                width: ${usedWidthPercent}%;
                height: ${usedHeightPercent}%;
                padding: 5px;
                box-sizing: border-box;
            ">
        `;

        for (let i = 0; i < layout.totalBoxes; i++) {
            html += `
                <div class="co-box" style="aspect-ratio: ${layout.boxWidth} / ${layout.boxHeight};">
                    <span class="co-box-number">#${boxCounter++}</span>
                    <div style="font-size: 8px; margin-top: 2px;">${layout.boxWidth}×${layout.boxHeight}</div>
                </div>
            `;
        }

        html += `</div>`;
    } else {
        // Complex recursive layout - render with absolute positioning
        html += `<div style="width: 100%; height: 100%; padding: 5px; box-sizing: border-box; display: flex; flex-wrap: wrap; gap: 6px;">`;

        // Render main grid area if it exists
        if (layout.mainBoxes > 0) {
            const mainUsedWidth = layout.numStrips > 0 ? layout.numStrips * (layout.boxWidth + optimizer.gap) - optimizer.gap : 0;
            const mainUsedHeight = layout.boxesPerStrip > 0 ? layout.boxesPerStrip * (layout.boxHeight + optimizer.gap) - optimizer.gap : 0;
            const mainWidthPercent = (mainUsedWidth / actualSheetWidth) * 100;
            const mainHeightPercent = (mainUsedHeight / actualSheetHeight) * 100;

            if (layout.layoutType === 'vertical') {
                // Vertical strips layout
                for (let col = 0; col < layout.numStrips; col++) {
                    const colLeft = (col * (layout.boxWidth + optimizer.gap) / actualSheetWidth) * 100;
                    const colWidth = (layout.boxWidth / actualSheetWidth) * 100;

                    html += `<div style="display: flex; flex-direction: column; justify-content: space-between; flex: max(calc( ${layout.boxWidth} / ${layout.boxHeight}),1); gap: 6px;">`;

                    for (let row = 0; row < layout.boxesPerStrip; row++) {
                        const rowTop = (row * (layout.boxHeight + optimizer.gap) / actualSheetHeight) * 100;
                        const rowHeight = (layout.boxHeight / actualSheetHeight) * 100;

                        html += `
                            <div class="co-box" style="aspect-ratio: ${layout.boxWidth} / ${layout.boxHeight};">
                                <span class="co-box-number">#${boxCounter++}</span>
                                <div style="font-size: 8px; margin-top: 2px;">${layout.boxWidth}×${layout.boxHeight}</div>
                            </div>
                        `;
                    }

                    html += `</div>`
                }
            } else {
                // Horizontal strips layout
                for (let row = 0; row < layout.numStrips; row++) {
                    const rowTop = (row * (layout.boxHeight + optimizer.gap) / actualSheetHeight) * 100;
                    const rowHeight = (layout.boxHeight / actualSheetHeight) * 100;
                    const rowWidthPercent = ((layout.boxWidth * layout.boxesPerStrip) + (optimizer.gap * (layout.boxesPerStrip + 1))) / actualSheetWidth * 100;

                    html += `<div style="width: ${rowWidthPercent}%; display: flex; flex-direction: row; justify-content: space-between; gap: 6px;">`;

                    for (let col = 0; col < layout.boxesPerStrip; col++) {
                        const colLeft = (col * (layout.boxWidth + optimizer.gap) / actualSheetWidth) * 100;
                        const colWidth = (layout.boxWidth / actualSheetWidth) * 100;

                        html += `
                            <div class="co-box" style="aspect-ratio: ${layout.boxWidth} / ${layout.boxHeight};">
                                <span class="co-box-number">#${boxCounter++}</span>
                                <div style="font-size: 8px; margin-top: 2px;">${layout.boxWidth}×${layout.boxHeight}</div>
                            </div>
                        `;
                    }

                    html += `</div>`
                }
            }
        }

        // Render remaining details (recursive boxes)
        if (layout.remainingDetails && layout.remainingDetails.length > 0) {
            // Calculate offset based on main layout
            let offsetLeft = 0;
            let offsetTop = 0;

            if (layout.layoutType === 'vertical') {
                // Main boxes are vertical strips, remaining space is on the right
                offsetLeft = layout.mainBoxes > 0 ? (layout.numStrips * (layout.boxWidth + optimizer.gap)) : 0;
                offsetTop = 0;
            } else {
                // Main boxes are horizontal strips, remaining space is on the bottom
                offsetLeft = 0;
                offsetTop = layout.mainBoxes > 0 ? (layout.numStrips * (layout.boxHeight + optimizer.gap)) : 0;
            }

            // Render each detail area
            layout.remainingDetails.forEach((detail, detailIndex) => {
                if (layout.layoutType === 'vertical') {
                    // VERTICAL: Each column is a separate div with rows inside
                    for (let col = 0; col < detail.cols; col++) {
                        // Start column container
                        html += `<div style="display: flex; flex-direction: column; gap: 6px; flex: max(calc(${detail.boxWidth} / ${detail.boxHeight}),1);">`;

                        for (let row = 0; row < detail.rows; row++) {
                            html += `
                                <div class="co-box co-box-rotated" style="flex: 1; aspect-ratio: ${detail.boxWidth} / ${detail.boxHeight};">
                                    <span class="co-box-number">#${boxCounter++}</span>
                                    <div style="font-size: 8px; margin-top: 2px;">${detail.boxWidth}×${detail.boxHeight}</div>
                                </div>
                            `;
                        }

                        // Close column container
                        html += `</div>`;
                    }
                } else {
                    // HORIZONTAL: Each row is a separate div with columns inside
                    for (let row = 0; row < detail.rows; row++) {
                        const detailRowWidthPercent = ((detail.boxWidth * detail.cols) + (optimizer.gap * (detail.cols + 1))) / actualSheetWidth * 100;

                        // Start row container
                        html += `<div style="display: flex; flex-direction: row; gap: 6px; width: ${detailRowWidthPercent}%;">`;

                        for (let col = 0; col < detail.cols; col++) {
                            html += `
                                <div class="co-box co-box-rotated" style="flex: 1; aspect-ratio: ${detail.boxWidth} / ${detail.boxHeight};">
                                    <span class="co-box-number">#${boxCounter++}</span>
                                    <div style="font-size: 8px; margin-top: 2px;">${detail.boxWidth}×${detail.boxHeight}</div>
                                </div>
                            `;
                        }

                        // Close row container
                        html += `</div>`;
                    }
                }

                // Update offset for next detail (simple stacking - may need refinement)
                if (layout.layoutType === 'vertical') {
                    offsetLeft += detail.cols * (detail.boxWidth + optimizer.gap);
                } else {
                    offsetTop += detail.rows * (detail.boxHeight + optimizer.gap);
                }
            });
        }

        html += `</div>`;
    }

    html += `
        </div>
        <div class="co-waste-info">
            <p><strong>Layout Type:</strong> ${layout.layoutType === 'vertical' ? 'Vertical' : 'Horizontal'} Strips ${!isSimpleLayout ? 'with Recursive Filling' : ''}</p>
            <p><strong>Total Boxes:</strong> ${layout.totalBoxes}</p>
            ${layout.mainBoxes > 0 ? `<p><strong>Main Grid:</strong> ${layout.mainBoxes} boxes (${layout.cols || layout.numStrips} × ${layout.rows || layout.boxesPerStrip})</p>` : ''}
            ${layout.remainingDetails && layout.remainingDetails.length > 0 ? `<p><strong>Additional Areas:</strong> ${layout.rotatedBoxes} boxes in ${layout.remainingDetails.length} area(s)</p>` : ''}
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

    // Render results for separate lid mode
    function renderSeparateLidResults(lidOptimizer) {
        const strategy = lidOptimizer.findOptimalStrategy();

        if (strategy.error) {
            return `<div class="co-summary"><h2>${strategy.error}</h2></div>`;
        }

        const { combined, separate, allCombinedConfigs, recommendation, reason } = strategy;

        // Store for click handling
        currentStrategy = strategy;
        currentSelectedMode = recommendation;
        currentCombinedConfigs = allCombinedConfigs;

        let html = `
            <div class="co-lid-summary">
                <h4><span class="dashicons dashicons-archive"></span> Separate Lid Mode</h4>
                <p><strong>Box:</strong> ${lidOptimizer.boxWidth} × ${lidOptimizer.boxHeight} cm</p>
                <p><strong>Lid:</strong> ${lidOptimizer.lidWidth} × ${lidOptimizer.lidHeight} cm</p>
                <p><strong>Sheet:</strong> ${lidOptimizer.sheetWidth} × ${lidOptimizer.sheetHeight} cm</p>
            </div>

            <div class="co-summary">
                <h2><span class="dashicons dashicons-yes-alt"></span> Optimal Strategy Found</h2>
                <div class="co-summary-grid">
                    <div class="co-summary-item">
                        <label>Recommendation</label>
                        <div class="value">${recommendation === 'combined' ? 'Combined Sheet' : 'Separate Sheets'}</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Max Pairs</label>
                        <div class="value">${recommendation === 'combined' ? combined.pairs : separate.pairs}</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Sheets Needed</label>
                        <div class="value">${recommendation === 'combined' ? '1' : '2'}</div>
                    </div>
                    <div class="co-summary-item">
                        <label>Efficiency</label>
                        <div class="value">${(recommendation === 'combined' ? combined.efficiency : separate.efficiency).toFixed(2)}%</div>
                    </div>
                </div>
                <p style="margin-top: 15px; opacity: 0.9;"><em>${reason}</em></p>
            </div>
        `;

        // Comparison view with clickable options
        html += `<div class="co-comparison-view">`;

        // Combined sheet option (clickable)
        if (combined) {
            html += renderCombinedOption(combined, lidOptimizer, recommendation === 'combined');
        }

        // Separate sheets option (clickable)
        if (separate) {
            html += renderSeparateOption(separate, lidOptimizer, recommendation === 'separate');
        }

        html += `</div>`;

        // Dynamic content container for mode-specific content
        html += `<div class="co-mode-content">`;

        // Show detailed diagram and configurations based on selected mode
        if (recommendation === 'combined' && combined) {
            html += renderCombinedModeContent(combined, allCombinedConfigs, lidOptimizer);
        } else if (recommendation === 'separate' && separate) {
            html += renderSeparateModeContent(separate, lidOptimizer);
        }

        html += `</div>`;

        return html;
    }

    // Render content for combined mode (diagram + configurations)
    function renderCombinedModeContent(combinedResult, allConfigs, lidOptimizer) {
        let html = '';

        // Show the main combined sheet diagram
        html += renderCombinedSheetDiagram(combinedResult, lidOptimizer);

        // Show all combined configurations
        if (allConfigs && allConfigs.length > 0) {
            const maxPairs = allConfigs[0].pairs;
            const optimalConfigs = allConfigs.filter(c => c.pairs === maxPairs);
            const efficientConfigs = allConfigs.filter(c => c.pairs < maxPairs && c.efficiency > 60);

            html += `
                <div class="co-layouts co-combined-layouts">
                    <h3><span class="dashicons dashicons-format-gallery"></span> Combined Sheet Configurations</h3>
                    <div class="co-optimal-badge co-combined-badge">
                        <span class="dashicons dashicons-star-filled"></span>
                        ${optimalConfigs.length} Optimal Configuration${optimalConfigs.length > 1 ? 's' : ''} (${maxPairs} pairs each)
                    </div>
            `;

            // Optimal configurations
            optimalConfigs.forEach((config, index) => {
                html += renderCombinedConfigItem(config, index, index, true, lidOptimizer);
            });

            // Other efficient configurations
            if (efficientConfigs.length > 0) {
                html += `<h4 style="margin-top: 25px; color: #646970;">Other Configurations</h4>`;
                html += `<p style="color: #666; margin-bottom: 20px;">Showing ${Math.min(efficientConfigs.length, 10)} additional configurations</p>`;

                efficientConfigs.slice(0, 10).forEach((config, index) => {
                    html += renderCombinedConfigItem(config, optimalConfigs.length + index, index, false, lidOptimizer);
                });
            }

            html += `</div>`;
        }

        return html;
    }

    // Render content for separate mode (diagram + box/lid layouts)
    function renderSeparateModeContent(separateResult, lidOptimizer) {
        let html = '';

        // Show the separate sheets diagram
        html += renderSeparateSheetsDiagram(separateResult, lidOptimizer);

        // Show all efficient layouts for boxes and lids
        html += renderEfficientLayoutsForLidMode(separateResult, lidOptimizer);

        return html;
    }

    // Render a combined configuration item
    function renderCombinedConfigItem(config, globalIndex, displayIndex, isOptimal, lidOptimizer) {
        const approachLabel = config.approach === 'split'
            ? `${config.splitType === 'vertical' ? 'Vertical' : 'Horizontal'} Split at ${(config.splitRatio * 100).toFixed(0)}%${config.swapped ? ' (swapped)' : ''}`
            : 'Mixed Placement';

        return `
            <div class="co-layout-item co-combined-item ${isOptimal ? 'optimal' : ''}" data-config-index="${globalIndex}" data-type="combined-config">
                <div class="co-layout-header">
                    <div class="co-layout-title">
                        ${isOptimal ? '<span class="dashicons dashicons-star-filled" style="color: #2271b1;"></span>' : ''}
                        ${approachLabel} (Config ${displayIndex + 1})
                    </div>
                    <div class="co-layout-boxes">${config.pairs} pairs</div>
                </div>

                <div class="co-layout-stats">
                    <div class="co-stat">
                        <label>Boxes</label>
                        <div class="value">${config.boxCount}</div>
                    </div>
                    <div class="co-stat">
                        <label>Lids</label>
                        <div class="value">${config.lidCount}</div>
                    </div>
                    <div class="co-stat">
                        <label>Efficiency</label>
                        <div class="value">${config.efficiency.toFixed(2)}%</div>
                    </div>
                    <div class="co-stat">
                        <label>Sheets</label>
                        <div class="value">1</div>
                    </div>
                </div>

                <div class="co-efficiency-bar">
                    <label>Material Efficiency</label>
                    <div class="co-efficiency-track">
                        <div class="co-efficiency-fill co-combined-fill" style="width: ${config.efficiency}%">
                            ${config.efficiency.toFixed(1)}%
                        </div>
                    </div>
                </div>

                <div class="co-layout-details co-combined-details">
                    <p><strong>Approach:</strong> ${config.approach === 'split' ? 'Sheet Split' : 'Mixed Placement'}</p>
                    ${config.approach === 'split' ? `
                        <p><strong>Split:</strong> ${config.splitType} at ${(config.splitRatio * 100).toFixed(0)}%</p>
                        <p><strong>Region 1:</strong> ${config.swapped ? 'Lids' : 'Boxes'} (${config.swapped ? config.lidCount : config.boxCount})</p>
                        <p><strong>Region 2:</strong> ${config.swapped ? 'Boxes' : 'Lids'} (${config.swapped ? config.boxCount : config.lidCount})</p>
                    ` : `
                        <p><strong>Boxes:</strong> ${config.boxCount} in main area</p>
                        <p><strong>Lids:</strong> ${config.lidCount} in remaining space</p>
                    `}
                    <p><strong>Used Area:</strong> ${config.usedArea.toFixed(0)} cm²</p>
                    <p><strong>Wasted Area:</strong> ${config.wastedArea.toFixed(0)} cm²</p>
                </div>
            </div>
        `;
    }

    // Render efficient layouts for both boxes and lids in lid mode
    function renderEfficientLayoutsForLidMode(separateResult, lidOptimizer) {
        let html = '';

        // Get all box layouts
        const boxOptimizer = new CuttingOptimizer(
            lidOptimizer.boxWidth, lidOptimizer.boxHeight,
            lidOptimizer.sheetWidth, lidOptimizer.sheetHeight, lidOptimizer.gap
        );
        const boxLayouts = boxOptimizer.findOptimalLayout();

        // Get all lid layouts
        const lidOptimizerCalc = new CuttingOptimizer(
            lidOptimizer.lidWidth, lidOptimizer.lidHeight,
            lidOptimizer.sheetWidth, lidOptimizer.sheetHeight, lidOptimizer.gap
        );
        const lidLayouts = lidOptimizerCalc.findOptimalLayout();

        // Store for click handling
        currentBoxLayouts = boxLayouts;
        currentLidLayouts = lidLayouts;
        currentBoxOptimizerForLidMode = boxOptimizer;
        currentLidOptimizerForLidMode = lidOptimizerCalc;

        // Filter efficient box layouts (>80%)
        const maxBoxCount = boxLayouts.length > 0 ? boxLayouts[0].totalBoxes : 0;
        const optimalBoxLayouts = boxLayouts.filter(layout => layout.totalBoxes === maxBoxCount);
        const efficientBoxLayouts = boxLayouts.filter(layout =>
            layout.efficiency > 80 && layout.totalBoxes < maxBoxCount
        );

        // Filter efficient lid layouts (>80%)
        const maxLidCount = lidLayouts.length > 0 ? lidLayouts[0].totalBoxes : 0;
        const optimalLidLayouts = lidLayouts.filter(layout => layout.totalBoxes === maxLidCount);
        const efficientLidLayouts = lidLayouts.filter(layout =>
            layout.efficiency > 80 && layout.totalBoxes < maxLidCount
        );

        // Render Box Layouts Section
        if (optimalBoxLayouts.length > 0) {
            html += `
                <div class="co-layouts">
                    <h3><span class="dashicons dashicons-grid-view"></span> Box Layouts (${lidOptimizer.boxWidth} × ${lidOptimizer.boxHeight} cm)</h3>
                    <div class="co-optimal-badge">
                        <span class="dashicons dashicons-star-filled"></span>
                        ${optimalBoxLayouts.length} Optimal Solution${optimalBoxLayouts.length > 1 ? 's' : ''} (${maxBoxCount} boxes each)
                    </div>
            `;

            // Show visual diagram for best box layout
            html += `<div class="co-box-diagram-container">`;
            html += renderVisualDiagram(optimalBoxLayouts[0], boxOptimizer, 'box-0');
            html += `</div>`;

            // Optimal box layouts
            optimalBoxLayouts.forEach((layout, index) => {
                const layoutIndex = boxLayouts.indexOf(layout);
                html += renderLayoutItem(layout, layoutIndex, index, true, 'box');
            });

            // Efficient box layouts
            if (efficientBoxLayouts.length > 0) {
                html += `<h4 style="margin-top: 25px; color: #646970;">Other Efficient Options (>80% Efficiency)</h4>`;
                html += `<p style="color: #666; margin-bottom: 20px;">Showing ${efficientBoxLayouts.length} additional efficient layouts</p>`;

                efficientBoxLayouts.slice(0, 10).forEach((layout, index) => {
                    const layoutIndex = boxLayouts.indexOf(layout);
                    html += renderLayoutItem(layout, layoutIndex, index, false, 'box');
                });
            }

            html += `</div>`;
        }

        // Render Lid Layouts Section
        if (optimalLidLayouts.length > 0) {
            html += `
                <div class="co-layouts co-lid-layouts">
                    <h3><span class="dashicons dashicons-archive"></span> Lid Layouts (${lidOptimizer.lidWidth} × ${lidOptimizer.lidHeight} cm)</h3>
                    <div class="co-optimal-badge co-lid-badge">
                        <span class="dashicons dashicons-star-filled"></span>
                        ${optimalLidLayouts.length} Optimal Solution${optimalLidLayouts.length > 1 ? 's' : ''} (${maxLidCount} lids each)
                    </div>
            `;

            // Show visual diagram for best lid layout
            html += `<div class="co-lid-diagram-container">`;
            html += renderVisualDiagramForLids(optimalLidLayouts[0], lidOptimizerCalc, 'lid-0');
            html += `</div>`;

            // Optimal lid layouts
            optimalLidLayouts.forEach((layout, index) => {
                const layoutIndex = lidLayouts.indexOf(layout);
                html += renderLayoutItem(layout, layoutIndex, index, true, 'lid');
            });

            // Efficient lid layouts
            if (efficientLidLayouts.length > 0) {
                html += `<h4 style="margin-top: 25px; color: #646970;">Other Efficient Options (>80% Efficiency)</h4>`;
                html += `<p style="color: #666; margin-bottom: 20px;">Showing ${efficientLidLayouts.length} additional efficient layouts</p>`;

                efficientLidLayouts.slice(0, 10).forEach((layout, index) => {
                    const layoutIndex = lidLayouts.indexOf(layout);
                    html += renderLayoutItem(layout, layoutIndex, index, false, 'lid');
                });
            }

            html += `</div>`;
        }

        return html;
    }

    // Render a layout item (reusable for both boxes and lids)
    function renderLayoutItem(layout, layoutIndex, displayIndex, isOptimal, type) {
        const itemClass = type === 'lid' ? 'co-layout-item co-lid-item' : 'co-layout-item';
        const optimalClass = isOptimal ? 'optimal' : '';
        const label = type === 'lid' ? 'lids' : 'boxes';
        const starColor = type === 'lid' ? '#f0a000' : '#46b450';

        return `
            <div class="${itemClass} ${optimalClass}" data-layout-index="${layoutIndex}" data-type="${type}">
                <div class="co-layout-header">
                    <div class="co-layout-title">
                        ${isOptimal ? `<span class="dashicons dashicons-star-filled" style="color: ${starColor};"></span>` : ''}
                        ${layout.name} (Config ${displayIndex + 1})
                    </div>
                    <div class="co-layout-boxes ${type === 'lid' ? 'co-lid-count' : ''}">${layout.totalBoxes} ${label}</div>
                </div>

                <div class="co-layout-stats">
                    <div class="co-stat">
                        <label>Used Area</label>
                        <div class="value">${layout.usedArea.toFixed(2)} cm²</div>
                    </div>
                    <div class="co-stat">
                        <label>Wasted Area</label>
                        <div class="value">${layout.wastedArea.toFixed(2)} cm²</div>
                    </div>
                    <div class="co-stat">
                        <label>Efficiency</label>
                        <div class="value">${layout.efficiency.toFixed(2)}%</div>
                    </div>
                </div>

                <div class="co-efficiency-bar">
                    <label>Material Efficiency</label>
                    <div class="co-efficiency-track">
                        <div class="co-efficiency-fill ${type === 'lid' ? 'co-lid-fill' : ''}" style="width: ${layout.efficiency}%">
                            ${layout.efficiency.toFixed(1)}%
                        </div>
                    </div>
                </div>

                <div class="co-layout-details ${type === 'lid' ? 'co-lid-details' : ''}">
                    <p><strong>Layout Type:</strong> ${layout.layoutType === 'vertical' ? 'Vertical Strips' : 'Horizontal Strips'}</p>
                    ${layout.mainBoxes > 0 ? `<p><strong>Main ${type === 'lid' ? 'Lids' : 'Boxes'}:</strong> ${layout.mainBoxes} ${label} (${layout.numStrips} strips × ${layout.boxesPerStrip} ${label} per strip) - Orientation: ${layout.mainOrientation}</p>` : ''}
                    ${layout.remainingDetails && layout.remainingDetails.length > 0 ? `
                        <p><strong>Additional ${type === 'lid' ? 'Lids' : 'Boxes'} in Remaining Space:</strong></p>
                        <ul style="margin: 5px 0; padding-left: 20px;">
                            ${layout.remainingDetails.map(detail =>
                                `<li>${detail.boxes} ${label} (${detail.cols} cols × ${detail.rows} rows) - ${detail.orientation} ${detail.isRotated ? '(rotated)' : ''}</li>`
                            ).join('')}
                        </ul>
                    ` : ''}
                    <p><strong>Used Dimensions:</strong> ${layout.usedWidth.toFixed(1)} × ${layout.usedHeight.toFixed(1)} cm</p>
                    <p><strong>Waste:</strong> ${layout.wasteWidth.toFixed(1)} cm (width) × ${layout.wasteHeight.toFixed(1)} cm (height)</p>
                </div>
            </div>
        `;
    }

    function renderCombinedOption(result, lidOptimizer, isRecommended) {
        return `
            <div class="co-comparison-option co-option-clickable ${isRecommended ? 'recommended' : ''} ${currentSelectedMode === 'combined' ? 'selected' : ''}" data-mode="combined">
                ${isRecommended ? '<div class="co-recommended-badge"><span class="dashicons dashicons-star-filled"></span> Recommended</div>' : ''}
                <h4><span class="dashicons dashicons-format-gallery"></span> Combined Sheet</h4>
                <div class="co-option-stats">
                    <div class="co-option-stat">
                        <label>Pairs</label>
                        <div class="value">${result.pairs}</div>
                    </div>
                    <div class="co-option-stat">
                        <label>Sheets</label>
                        <div class="value">1</div>
                    </div>
                    <div class="co-option-stat">
                        <label>Efficiency</label>
                        <div class="value">${result.efficiency.toFixed(1)}%</div>
                    </div>
                    <div class="co-option-stat">
                        <label>Approach</label>
                        <div class="value">${result.approach === 'split' ? 'Split' : 'Mixed'}</div>
                    </div>
                </div>
                <p style="font-size: 12px; color: #666;">
                    ${result.approach === 'split'
                        ? `Sheet split ${result.splitType}ly at ${(result.splitRatio * 100).toFixed(0)}%`
                        : 'Lids placed in remaining space after boxes'}
                </p>
                <div class="co-mini-diagram">
                    ${renderCombinedMiniDiagram(result, lidOptimizer)}
                </div>
            </div>
        `;
    }

    function renderSeparateOption(result, lidOptimizer, isRecommended) {
        return `
            <div class="co-comparison-option co-option-clickable ${isRecommended ? 'recommended' : ''} ${currentSelectedMode === 'separate' ? 'selected' : ''}" data-mode="separate">
                ${isRecommended ? '<div class="co-recommended-badge"><span class="dashicons dashicons-star-filled"></span> Recommended</div>' : ''}
                <h4><span class="dashicons dashicons-images-alt2"></span> Separate Sheets</h4>
                <div class="co-option-stats">
                    <div class="co-option-stat">
                        <label>Pairs</label>
                        <div class="value">${result.pairs}</div>
                    </div>
                    <div class="co-option-stat">
                        <label>Sheets</label>
                        <div class="value">2</div>
                    </div>
                    <div class="co-option-stat">
                        <label>Efficiency</label>
                        <div class="value">${result.efficiency.toFixed(1)}%</div>
                    </div>
                    <div class="co-option-stat">
                        <label>Max Boxes/Lids</label>
                        <div class="value">${result.maxBoxes}/${result.maxLids}</div>
                    </div>
                </div>
                <p style="font-size: 12px; color: #666;">
                    Sheet 1: ${result.maxBoxes} boxes, Sheet 2: ${result.maxLids} lids
                </p>
                <div class="co-mini-diagram">
                    <div class="co-separate-sheets">
                        <div>
                            <div class="co-mini-sheet" style="aspect-ratio: ${lidOptimizer.sheetWidth}/${lidOptimizer.sheetHeight};">
                                ${renderMiniBoxes(result.maxBoxes, 'box')}
                            </div>
                            <div class="co-sheet-label">Boxes (${result.maxBoxes})</div>
                        </div>
                        <div>
                            <div class="co-mini-sheet" style="aspect-ratio: ${lidOptimizer.sheetWidth}/${lidOptimizer.sheetHeight};">
                                ${renderMiniBoxes(result.maxLids, 'lid')}
                            </div>
                            <div class="co-sheet-label">Lids (${result.maxLids})</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderCombinedMiniDiagram(result, lidOptimizer) {
        const sheetWidth = lidOptimizer.sheetWidth;
        const sheetHeight = lidOptimizer.sheetHeight;

        if (result.approach === 'split') {
            const splitPos = result.splitRatio * 100;
            const isVertical = result.splitType === 'vertical';

            return `
                <div class="co-mini-sheet" style="aspect-ratio: ${sheetWidth}/${sheetHeight};">
                    <div class="co-split-line ${result.splitType}" style="${isVertical ? `left: ${splitPos}%` : `top: ${splitPos}%`}"></div>
                    <div style="${isVertical ? `width: ${splitPos}%` : `height: ${splitPos}%`}; ${isVertical ? 'height: 100%' : 'width: 100%'}; display: flex; flex-wrap: wrap; gap: 2px; padding: 3px; box-sizing: border-box;">
                        ${renderMiniBoxes(result.swapped ? result.lidCount : result.boxCount, result.swapped ? 'lid' : 'box')}
                    </div>
                    <div style="${isVertical ? `width: ${100 - splitPos}%` : `height: ${100 - splitPos}%`}; ${isVertical ? 'height: 100%' : 'width: 100%'}; display: flex; flex-wrap: wrap; gap: 2px; padding: 3px; box-sizing: border-box;">
                        ${renderMiniBoxes(result.swapped ? result.boxCount : result.lidCount, result.swapped ? 'box' : 'lid')}
                    </div>
                </div>
            `;
        } else {
            // Mixed placement
            return `
                <div class="co-mini-sheet" style="aspect-ratio: ${sheetWidth}/${sheetHeight}; display: flex; flex-wrap: wrap; gap: 2px; padding: 3px; align-content: flex-start;">
                    ${renderMiniBoxes(result.boxCount, 'box')}
                    ${renderMiniBoxes(result.lidCount, 'lid')}
                </div>
            `;
        }
    }

    function renderMiniBoxes(count, type) {
        let html = '';
        const displayCount = Math.min(count, 50); // Limit for display
        for (let i = 0; i < displayCount; i++) {
            html += `<div class="co-mini-${type}"></div>`;
        }
        if (count > 50) {
            html += `<div style="font-size: 8px; color: #666;">+${count - 50}</div>`;
        }
        return html;
    }

    function renderCombinedSheetDiagram(result, lidOptimizer) {
        let html = `
            <div class="co-visual-diagram" id="visual-diagram-combined">
                <h3><span class="dashicons dashicons-visibility"></span> Combined Sheet Layout</h3>
                <div class="co-diagram-container">
        `;

        const sheetWidth = lidOptimizer.sheetWidth;
        const sheetHeight = lidOptimizer.sheetHeight;
        const gap = lidOptimizer.gap;

        html += `
            <div class="co-sheet" style="width: 100%; aspect-ratio: ${sheetWidth} / ${sheetHeight}; position: relative; display: flex; gap: 6px;">
                <div class="co-sheet-label-width">${sheetWidth} cm</div>
                <div class="co-sheet-label-width-left-line"></div>
                <div class="co-sheet-label-width-right-line"></div>
                <div class="co-sheet-label-height">${sheetHeight}<br/>cm</div>
                <div class="co-sheet-label-height-bottom-line"></div>
                <div class="co-sheet-label-height-top-line"></div>
        `;

        if (result.approach === 'split') {
            const isVertical = result.splitType === 'vertical';

            // Get layouts for each region
            const boxLayout = result.boxLayout;
            const lidLayout = result.lidLayout;

            // Region 1 - Boxes or Lids based on swapped
            const region1Layout = result.swapped ? lidLayout : boxLayout;
            const region1Type = result.swapped ? 'lid' : 'box';

            // Region 2 - The other type
            const region2Layout = result.swapped ? boxLayout : lidLayout;
            const region2Type = result.swapped ? 'box' : 'lid';

            // Calculate actual used dimensions for each region
            const region1UsedWidth = region1Layout.usedWidth;
            const region1UsedHeight = region1Layout.usedHeight;
            const region2UsedWidth = region2Layout.usedWidth;
            const region2UsedHeight = region2Layout.usedHeight;

            // Calculate percentages based on actual used dimensions
            let region1WidthPercent, region1HeightPercent;
            let region2LeftPercent, region2TopPercent, region2WidthPercent, region2HeightPercent;
            let splitLinePos;

            if (isVertical) {
                // Vertical split - regions side by side
                region1WidthPercent = (region1UsedWidth / sheetWidth) * 100;
                region1HeightPercent = (region1UsedHeight / sheetHeight) * 100;

                region2WidthPercent = (region2UsedWidth / sheetWidth) * 100;
                region2HeightPercent = (region2UsedHeight / sheetHeight) * 100;
                region2LeftPercent = ((region1UsedWidth + gap) / sheetWidth) * 100;
                region2TopPercent = 0;

                splitLinePos = region1WidthPercent;
            } else {
                // Horizontal split - regions stacked
                region1WidthPercent = (region1UsedWidth / sheetWidth) * 100;
                region1HeightPercent = (region1UsedHeight / sheetHeight) * 100;

                region2WidthPercent = (region2UsedWidth / sheetWidth) * 100;
                region2HeightPercent = (region2UsedHeight / sheetHeight) * 100;
                region2LeftPercent = 0;
                region2TopPercent = ((region1UsedHeight + gap) / sheetHeight) * 100;

                splitLinePos = region1HeightPercent;
            }

            // Split line positioned at actual boundary
            html += `<div class="co-split-line ${result.splitType}" style="${isVertical ? `left: ${splitLinePos}%` : `top: ${splitLinePos}%`}"></div>`;

            // Render Region 1 with actual used dimensions
            const region1Style = isVertical
                ? `width: ${region1WidthPercent}%; height: ${region1HeightPercent}%;`
                : `width: ${region1WidthPercent}%; height: ${region1HeightPercent}%;`;

            html += `<div style="${region1Style} box-sizing: border-box;">`;
            html += renderCombinedRegionGrid(region1Layout, region1Type, region1UsedWidth, region1UsedHeight, gap);
            html += `</div>`;

            // Render Region 2 with actual used dimensions
            const region2Style = isVertical
                ? `width: ${region2WidthPercent}%; height: ${region2HeightPercent}%;`
                : `width: ${region2WidthPercent}%; height: ${region2HeightPercent}%;`;

            html += `<div style="${region2Style} box-sizing: border-box;">`;
            html += renderCombinedRegionGrid(region2Layout, region2Type, region2UsedWidth, region2UsedHeight, gap);
            html += `</div>`;

        } else {
            // Mixed placement - render boxes in main area, lids in remaining spaces
            const boxLayout = result.boxLayout;

            html += `<div style="width: 100%; height: 100%; box-sizing: border-box;">`;

            // Render boxes using grid layout
            html += renderCombinedRegionGrid(boxLayout, 'box', sheetWidth, sheetHeight, gap);

            // Render lids in remaining placements
            if (result.lidPlacements && result.lidPlacements.length > 0) {
                result.lidPlacements.forEach(placement => {
                    const offsetXPercent = (placement.offsetX / sheetWidth) * 100;
                    const offsetYPercent = (placement.offsetY / sheetHeight) * 100;
                    const widthPercent = (placement.width / sheetWidth) * 100;
                    const heightPercent = (placement.height / sheetHeight) * 100;

                    html += `<div style="width: ${widthPercent}%; height: ${heightPercent}%; padding: 2px; box-sizing: border-box;">`;
                    html += renderCombinedRegionGrid(placement.layout, 'lid', placement.width, placement.height, gap);
                    html += `</div>`;
                });
            }

            html += `</div>`;
        }

        html += `</div>`; // Close sheet

        html += `
            <div class="co-waste-info">
                <p><strong>Layout Approach:</strong> ${result.approach === 'split' ? `Split ${result.splitType} at ${(result.splitRatio * 100).toFixed(0)}%` : 'Mixed placement'}</p>
                <p><strong>Pairs:</strong> ${result.pairs} (${result.pairs} boxes + ${result.pairs} lids)</p>
                <p><strong>Efficiency:</strong> ${result.efficiency.toFixed(2)}%</p>
                <p><strong>Used Area:</strong> ${result.usedArea.toFixed(0)} cm²</p>
                <p><strong>Wasted Area:</strong> ${result.wastedArea.toFixed(0)} cm²</p>
            </div>
        `;

        html += `</div></div>`;
        return html;
    }

    // Render a grid of boxes/lids for a combined sheet region
    function renderCombinedRegionGrid(layout, type, regionWidth, regionHeight, gap) {
        if (!layout || layout.totalBoxes === 0) return '';

        let html = '';
        let counter = 1;
        const isLid = type === 'lid';
        const label = isLid ? 'L' : 'B';
        const boxClass = isLid ? 'co-box co-box-lid' : 'co-box';

        const isSimpleLayout = !layout.remainingDetails || layout.remainingDetails.length === 0;

        if (isSimpleLayout && layout.mainBoxes > 0) {
            // Simple grid layout using CSS Grid
            const usedWidthPercent = (layout.usedWidth / regionWidth) * 100;
            const usedHeightPercent = (layout.usedHeight / regionHeight) * 100;
            const boxWidthPercent = (layout.boxWidth / layout.usedWidth) * 100;
            const boxHeightPercent = (layout.boxHeight / layout.usedHeight) * 100;
            const gapWidthPercent = (gap / layout.usedWidth) * 100;
            const gapHeightPercent = (gap / layout.usedHeight) * 100;

            html += `
                <div class="co-box-grid" style="
                    display: grid;
                    grid-template-columns: repeat(${layout.cols}, ${boxWidthPercent}%);
                    grid-template-rows: repeat(${layout.rows}, ${boxHeightPercent}%);
                    gap: ${gapHeightPercent}% ${gapWidthPercent}%;
                    width: ${usedWidthPercent}%;
                    height: ${usedHeightPercent}%;
                ">
            `;

            for (let i = 0; i < layout.totalBoxes; i++) {
                html += `
                    <div class="${boxClass}" style="aspect-ratio: ${layout.boxWidth} / ${layout.boxHeight};">
                        <span class="co-box-number">${label}${counter++}</span>
                        <div style="font-size: 7px; margin-top: 1px;">${layout.boxWidth}×${layout.boxHeight}</div>
                    </div>
                `;
            }

            html += `</div>`;
        } else {
            // Complex layout with flexbox
            html += `<div style="width: 100%; height: 100%; display: flex; flex-wrap: wrap; gap: 6px; align-content: flex-start;">`;

            // Render main boxes
            if (layout.mainBoxes > 0) {
                if (layout.layoutType === 'vertical') {
                    for (let col = 0; col < layout.numStrips; col++) {
                        html += `<div style="display: flex; flex-direction: column; gap: 6px; flex: max(calc(${layout.boxWidth} / ${layout.boxHeight}), 1);">`;
                        for (let row = 0; row < layout.boxesPerStrip; row++) {
                            html += `
                                <div class="${boxClass}" style="aspect-ratio: ${layout.boxWidth} / ${layout.boxHeight};">
                                    <span class="co-box-number">${label}${counter++}</span>
                                    <div style="font-size: 7px;">${layout.boxWidth}×${layout.boxHeight}</div>
                                </div>
                            `;
                        }
                        html += `</div>`;
                    }
                } else {
                    for (let row = 0; row < layout.numStrips; row++) {
                        html += `<div style="width: 100%; display: flex; flex-direction: row; gap: 6px;">`;
                        for (let col = 0; col < layout.boxesPerStrip; col++) {
                            html += `
                                <div class="${boxClass}" style="flex: 1; aspect-ratio: ${layout.boxWidth} / ${layout.boxHeight};">
                                    <span class="co-box-number">${label}${counter++}</span>
                                    <div style="font-size: 7px;">${layout.boxWidth}×${layout.boxHeight}</div>
                                </div>
                            `;
                        }
                        html += `</div>`;
                    }
                }
            }

            // Render remaining details
            if (layout.remainingDetails && layout.remainingDetails.length > 0) {
                layout.remainingDetails.forEach(detail => {
                    const rotatedClass = isLid ? 'co-box-rotated-lid' : 'co-box-rotated';
                    if (layout.layoutType === 'vertical') {
                        for (let col = 0; col < detail.cols; col++) {
                            html += `<div style="display: flex; flex-direction: column; gap: 6px; flex: max(calc(${detail.boxWidth} / ${detail.boxHeight}), 1);">`;
                            for (let row = 0; row < detail.rows; row++) {
                                html += `
                                    <div class="${boxClass} ${rotatedClass}" style="aspect-ratio: ${detail.boxWidth} / ${detail.boxHeight};">
                                        <span class="co-box-number">${label}${counter++}</span>
                                        <div style="font-size: 7px;">${detail.boxWidth}×${detail.boxHeight}</div>
                                    </div>
                                `;
                            }
                            html += `</div>`;
                        }
                    } else {
                        for (let row = 0; row < detail.rows; row++) {
                            html += `<div style="width: 100%; display: flex; flex-direction: row; gap: 6px;">`;
                            for (let col = 0; col < detail.cols; col++) {
                                html += `
                                    <div class="${boxClass} ${rotatedClass}" style="flex: 1; aspect-ratio: ${detail.boxWidth} / ${detail.boxHeight};">
                                        <span class="co-box-number">${label}${counter++}</span>
                                        <div style="font-size: 7px;">${detail.boxWidth}×${detail.boxHeight}</div>
                                    </div>
                                `;
                            }
                            html += `</div>`;
                        }
                    }
                });
            }

            html += `</div>`;
        }

        return html;
    }

    function renderSeparateSheetsDiagram(result, lidOptimizer) {
        let html = `
            <div class="co-visual-diagram" id="visual-diagram-separate">
                <h3><span class="dashicons dashicons-visibility"></span> Separate Sheets Layout (Recommended)</h3>
                <p style="color: #666; margin-bottom: 15px;">Two sheets required: one for boxes, one for lids</p>
                <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
        `;

        // Box sheet diagram
        html += `
            <div class="co-diagram-container" style="padding: 20px;">
                <h4 style="margin: 0 0 10px 0;">Sheet 1: Boxes</h4>
        `;
        html += renderVisualDiagram(result.boxLayout, result.boxOptimizer, 'boxes');
        html += `</div>`;

        // Lid sheet diagram
        html += `
            <div class="co-diagram-container" style="padding: 20px;">
                <h4 style="margin: 0 0 10px 0;">Sheet 2: Lids</h4>
        `;
        html += renderVisualDiagramForLids(result.lidLayout, result.lidOptimizer);
        html += `</div>`;

        html += `</div>`;

        html += `
            <div class="co-waste-info">
                <p><strong>Total Pairs:</strong> ${result.pairs}</p>
                <p><strong>Boxes per sheet:</strong> ${result.maxBoxes} (using ${result.pairs})</p>
                <p><strong>Lids per sheet:</strong> ${result.maxLids} (using ${result.pairs})</p>
                <p><strong>Combined Efficiency:</strong> ${result.efficiency.toFixed(2)}%</p>
                <p><strong>Total Used Area:</strong> ${result.usedArea.toFixed(0)} cm² (across 2 sheets)</p>
            </div>
        `;

        html += `</div>`;
        return html;
    }

    function renderVisualDiagramForLids(layout, optimizer, layoutIndex = 0) {
        // Similar to renderVisualDiagram but with lid styling
        const actualSheetWidth = optimizer.sheetWidth;
        const actualSheetHeight = optimizer.sheetHeight;

        let html = `
        <div class="co-visual-diagram co-lid-diagram" id="visual-diagram-${layoutIndex}">
            <h3><span class="dashicons dashicons-visibility"></span> Visual Layout${layoutIndex === 'lid-0' ? " (Optimal)" : ""}</h3>
            <div class="co-diagram-container">
        `;

        html += `
            <div class="co-sheet" style="width: 100%; aspect-ratio: ${actualSheetWidth} / ${actualSheetHeight}; position: relative; border: 2px solid #333; background: #f9f9f9;">
                <div class="co-sheet-label-width">${actualSheetWidth} cm</div>
                <div class="co-sheet-label-width-left-line"></div>
                <div class="co-sheet-label-width-right-line"></div>
                <div class="co-sheet-label-height">${actualSheetHeight}<br/>cm</div>
                <div class="co-sheet-label-height-bottom-line"></div>
                <div class="co-sheet-label-height-top-line"></div>
        `;

        let boxCounter = 1;
        const isSimpleLayout = !layout.remainingDetails || layout.remainingDetails.length === 0;

        if (isSimpleLayout && layout.mainBoxes > 0) {
            const totalUsedWidth = layout.usedWidth;
            const totalUsedHeight = layout.usedHeight;
            const usedWidthPercent = (totalUsedWidth / actualSheetWidth) * 100;
            const usedHeightPercent = (totalUsedHeight / actualSheetHeight) * 100;
            const boxWidthPercent = (layout.boxWidth / totalUsedWidth) * 100;
            const boxHeightPercent = (layout.boxHeight / totalUsedHeight) * 100;
            const gapWidthPercent = (optimizer.gap / totalUsedWidth) * 100;
            const gapHeightPercent = (optimizer.gap / totalUsedHeight) * 100;

            html += `
                <div class="co-box-grid" style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    display: grid;
                    grid-template-columns: repeat(${layout.cols}, ${boxWidthPercent}%);
                    grid-template-rows: repeat(${layout.rows}, ${boxHeightPercent}%);
                    gap: ${gapHeightPercent}% ${gapWidthPercent}%;
                    width: ${usedWidthPercent}%;
                    height: ${usedHeightPercent}%;
                    padding: 5px;
                    box-sizing: border-box;
                ">
            `;

            for (let i = 0; i < layout.totalBoxes; i++) {
                html += `
                    <div class="co-box co-box-lid" style="aspect-ratio: ${layout.boxWidth} / ${layout.boxHeight};">
                        <span class="co-box-number">L${boxCounter++}</span>
                        <div style="font-size: 8px; margin-top: 2px;">${layout.boxWidth}×${layout.boxHeight}</div>
                    </div>
                `;
            }

            html += `</div>`;
        } else {
            // Complex recursive layout - render with flexbox like boxes
            html += `<div style="width: 100%; height: 100%; padding: 5px; box-sizing: border-box; display: flex; flex-wrap: wrap; gap: 6px;">`;

            // Render main grid area if it exists
            if (layout.mainBoxes > 0) {
                if (layout.layoutType === 'vertical') {
                    // Vertical strips layout
                    for (let col = 0; col < layout.numStrips; col++) {
                        html += `<div style="display: flex; flex-direction: column; justify-content: space-between; flex: max(calc( ${layout.boxWidth} / ${layout.boxHeight}),1); gap: 6px;">`;

                        for (let row = 0; row < layout.boxesPerStrip; row++) {
                            html += `
                                <div class="co-box co-box-lid" style="aspect-ratio: ${layout.boxWidth} / ${layout.boxHeight};">
                                    <span class="co-box-number">L${boxCounter++}</span>
                                    <div style="font-size: 8px; margin-top: 2px;">${layout.boxWidth}×${layout.boxHeight}</div>
                                </div>
                            `;
                        }

                        html += `</div>`;
                    }
                } else {
                    // Horizontal strips layout
                    for (let row = 0; row < layout.numStrips; row++) {
                        const rowWidthPercent = ((layout.boxWidth * layout.boxesPerStrip) + (optimizer.gap * (layout.boxesPerStrip + 1))) / actualSheetWidth * 100;

                        html += `<div style="width: ${rowWidthPercent}%; display: flex; flex-direction: row; justify-content: space-between; gap: 6px;">`;

                        for (let col = 0; col < layout.boxesPerStrip; col++) {
                            html += `
                                <div class="co-box co-box-lid" style="aspect-ratio: ${layout.boxWidth} / ${layout.boxHeight};">
                                    <span class="co-box-number">L${boxCounter++}</span>
                                    <div style="font-size: 8px; margin-top: 2px;">${layout.boxWidth}×${layout.boxHeight}</div>
                                </div>
                            `;
                        }

                        html += `</div>`;
                    }
                }
            }

            // Render remaining details (recursive lids)
            if (layout.remainingDetails && layout.remainingDetails.length > 0) {
                layout.remainingDetails.forEach((detail, detailIndex) => {
                    if (layout.layoutType === 'vertical') {
                        // VERTICAL: Each column is a separate div with rows inside
                        for (let col = 0; col < detail.cols; col++) {
                            html += `<div style="display: flex; flex-direction: column; gap: 6px; flex: max(calc(${detail.boxWidth} / ${detail.boxHeight}),1);">`;

                            for (let row = 0; row < detail.rows; row++) {
                                html += `
                                    <div class="co-box co-box-lid co-box-rotated-lid" style="flex: 1; aspect-ratio: ${detail.boxWidth} / ${detail.boxHeight};">
                                        <span class="co-box-number">L${boxCounter++}</span>
                                        <div style="font-size: 8px; margin-top: 2px;">${detail.boxWidth}×${detail.boxHeight}</div>
                                    </div>
                                `;
                            }

                            html += `</div>`;
                        }
                    } else {
                        // HORIZONTAL: Each row is a separate div with columns inside
                        for (let row = 0; row < detail.rows; row++) {
                            const detailRowWidthPercent = ((detail.boxWidth * detail.cols) + (optimizer.gap * (detail.cols + 1))) / actualSheetWidth * 100;

                            html += `<div style="display: flex; flex-direction: row; gap: 6px; width: ${detailRowWidthPercent}%;">`;

                            for (let col = 0; col < detail.cols; col++) {
                                html += `
                                    <div class="co-box co-box-lid co-box-rotated-lid" style="flex: 1; aspect-ratio: ${detail.boxWidth} / ${detail.boxHeight};">
                                        <span class="co-box-number">L${boxCounter++}</span>
                                        <div style="font-size: 8px; margin-top: 2px;">${detail.boxWidth}×${detail.boxHeight}</div>
                                    </div>
                                `;
                            }

                            html += `</div>`;
                        }
                    }
                });
            }

            html += `</div>`;
        }

        html += `
            </div>
            <div class="co-waste-info co-lid-waste-info">
                <p><strong>Layout Type:</strong> ${layout.layoutType === 'vertical' ? 'Vertical' : 'Horizontal'} Strips ${!isSimpleLayout ? 'with Recursive Filling' : ''}</p>
                <p><strong>Total Lids:</strong> ${layout.totalBoxes}</p>
                ${layout.mainBoxes > 0 ? `<p><strong>Main Grid:</strong> ${layout.mainBoxes} lids (${layout.cols || layout.numStrips} × ${layout.rows || layout.boxesPerStrip})</p>` : ''}
                ${layout.remainingDetails && layout.remainingDetails.length > 0 ? `<p><strong>Additional Areas:</strong> ${layout.rotatedBoxes} lids in ${layout.remainingDetails.length} area(s)</p>` : ''}
                <p><strong>Waste Areas:</strong></p>
                <p>Right edge: ${layout.wasteWidth.toFixed(1)} cm</p>
                <p>Bottom edge: ${layout.wasteHeight.toFixed(1)} cm</p>
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
    let currentLidOptimizer = null;
    let currentBoxLayouts = null;
    let currentLidLayouts = null;
    let currentBoxOptimizerForLidMode = null;
    let currentLidOptimizerForLidMode = null;
    let currentStrategy = null;
    let currentSelectedMode = null; // 'combined' or 'separate'
    let currentCombinedConfigs = null;

    // Bind all event handlers for lid mode
    function bindLidModeEventHandlers() {
        // 1. Click handler for switching between combined and separate modes
        $(".co-option-clickable").on("click", function () {
            const mode = $(this).data("mode");

            if (mode === currentSelectedMode) return; // Already selected

            currentSelectedMode = mode;

            // Update selected state on comparison options
            $(".co-comparison-option").removeClass("selected");
            $(this).addClass("selected");

            // Update mode content
            let newContent = '';
            if (mode === 'combined' && currentStrategy.combined) {
                newContent = renderCombinedModeContent(currentStrategy.combined, currentCombinedConfigs, currentLidOptimizer);
            } else if (mode === 'separate' && currentStrategy.separate) {
                newContent = renderSeparateModeContent(currentStrategy.separate, currentLidOptimizer);
            }

            $(".co-mode-content").html(newContent);

            // Re-bind event handlers for the new content
            bindLayoutItemClicks();
            bindCombinedConfigClicks();

            // Scroll to the mode content
            $("html, body").animate({
                scrollTop: $(".co-mode-content").offset().top - 100,
            }, 500);
        });

        // Bind layout item clicks and combined config clicks
        bindLayoutItemClicks();
        bindCombinedConfigClicks();
    }

    // Bind click handlers for box/lid layout items
    function bindLayoutItemClicks() {
        $(".co-layout-item[data-type='box'], .co-layout-item[data-type='lid']").off("click").on("click", function () {
            const layoutIndex = $(this).data("layout-index");
            const type = $(this).data("type");

            if (type === 'box' && currentBoxLayouts && currentBoxOptimizerForLidMode) {
                const selectedLayout = currentBoxLayouts[layoutIndex];
                const newDiagram = renderVisualDiagram(selectedLayout, currentBoxOptimizerForLidMode, 'box-' + layoutIndex);

                // Replace the box visual diagram
                $(".co-box-diagram-container").html(newDiagram);

                // Scroll to the diagram
                $("html, body").animate({
                    scrollTop: $(".co-box-diagram-container").offset().top - 100,
                }, 500);

                // Update selected state for box items only
                $(".co-layout-item[data-type='box']").removeClass("selected");
                $(this).addClass("selected");
            } else if (type === 'lid' && currentLidLayouts && currentLidOptimizerForLidMode) {
                const selectedLayout = currentLidLayouts[layoutIndex];
                const newDiagram = renderVisualDiagramForLids(selectedLayout, currentLidOptimizerForLidMode, 'lid-' + layoutIndex);

                // Replace the lid visual diagram
                $(".co-lid-diagram-container").html(newDiagram);

                // Scroll to the diagram
                $("html, body").animate({
                    scrollTop: $(".co-lid-diagram-container").offset().top - 100,
                }, 500);

                // Update selected state for lid items only
                $(".co-layout-item[data-type='lid']").removeClass("selected");
                $(this).addClass("selected");
            }
        });
    }

    // Bind click handlers for combined configuration items
    function bindCombinedConfigClicks() {
        $(".co-layout-item[data-type='combined-config']").off("click").on("click", function () {
            const configIndex = $(this).data("config-index");

            if (currentCombinedConfigs && currentCombinedConfigs[configIndex]) {
                const selectedConfig = currentCombinedConfigs[configIndex];

                // Update the combined sheet diagram
                const newDiagram = renderCombinedSheetDiagram(selectedConfig, currentLidOptimizer);
                $("#visual-diagram-combined").closest(".co-visual-diagram").replaceWith(newDiagram);

                // Scroll to the diagram
                $("html, body").animate({
                    scrollTop: $("#visual-diagram-combined").offset().top - 100,
                }, 500);

                // Update selected state
                $(".co-layout-item[data-type='combined-config']").removeClass("selected");
                $(this).addClass("selected");
            }
        });
    }

    $(document).ready(function () {
        $("#calculate-btn").on("click", function () {
            const boxWidth = parseFloat($("#box-width").val());
            const boxHeight = parseFloat($("#box-height").val());
            const sheetWidth = parseFloat($("#sheet-width").val());
            const sheetHeight = parseFloat($("#sheet-height").val());
            const gap = parseFloat($("#gap").val());

            // Check for separate lid dimensions
            const lidWidth = parseFloat($("#lid-width").val()) || 0;
            const lidHeight = parseFloat($("#lid-height").val()) || 0;
            const hasSeparateLid = lidWidth > 0 && lidHeight > 0;

            if (isNaN(boxWidth) || isNaN(boxHeight) || isNaN(sheetWidth) || isNaN(sheetHeight) || isNaN(gap)) {
                alert("Please enter valid numbers for all fields");
                return;
            }

            if (boxWidth <= 0 || boxHeight <= 0 || sheetWidth <= 0 || sheetHeight <= 0 || gap < 0) {
                alert("All dimensions must be positive numbers");
                return;
            }

            // Validate lid dimensions if provided
            if (hasSeparateLid && (lidWidth <= 0 || lidHeight <= 0)) {
                alert("Lid dimensions must be positive numbers");
                return;
            }

            $("#loading").show();
            $("#results").hide();

            setTimeout(function () {
                let resultsHtml;

                if (hasSeparateLid) {
                    // Separate lid mode
                    currentLidOptimizer = new SeparateLidOptimizer(
                        boxWidth, boxHeight, lidWidth, lidHeight,
                        sheetWidth, sheetHeight, gap
                    );
                    currentOptimizer = null;
                    currentLayouts = null;
                    resultsHtml = renderSeparateLidResults(currentLidOptimizer);
                } else {
                    // Standard mode (attached lid or no lid)
                    currentOptimizer = new CuttingOptimizer(boxWidth, boxHeight, sheetWidth, sheetHeight, gap);
                    currentLayouts = currentOptimizer.findOptimalLayout();
                    currentLidOptimizer = null;
                    resultsHtml = renderResults(currentOptimizer);
                }

                $("#results").html(resultsHtml).fadeIn();
                $("#loading").hide();

                if (!hasSeparateLid) {
                    // Standard mode - bind layout clicks
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
                } else {
                    // Lid mode - bind all event handlers
                    bindLidModeEventHandlers();
                }
            }, 500);
        });

        $(".co-input-group input, .co-lid-section input").on("keypress", function (e) {
            if (e.which === 13) {
                $("#calculate-btn").click();
            }
        });
    });
})(jQuery);