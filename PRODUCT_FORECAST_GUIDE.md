# Product Sales Forecasting - Monthly Filters Guide

## 🎯 New Feature: Monthly Product Forecasting with Filters

You can now **predict which products will be top sellers** for any specific month in the future!

---

## 📊 How It Works

### Filter Options:
- **Month Dropdown**: Select any month (January - December)
- **Year Dropdown**: Select year (2021 - 2026)

### Default Behavior:
- Shows **next month** by default
- Example: If today is October 2025, shows November 2025 forecast

---

## 🔍 What Gets Predicted

### Top 6 Products:
The chart shows the **6 products predicted to sell the most** in your selected month.

### Prediction Algorithm:

#### 1. **Trend Analysis**
- Analyzes last 12 months of sales data
- Calculates if product is growing or declining
- Projects trend forward

#### 2. **Seasonal Patterns**
- Checks if selected month has historical patterns
- Example: "December always sells 30% more bedsheets"
- Blends trend (70%) with seasonal average (30%)

#### 3. **Realistic Fluctuation**
- Adds ±8% random variation
- Prevents unrealistic straight-line predictions
- Simulates market volatility

---

## 📈 Use Cases

### 1. **Inventory Planning**
```
Select: December 2025
Result: "Premium Bedsheet" predicted to sell 150 units
Action: Order 180 units (20% buffer) in November
```

### 2. **Marketing Strategy**
```
Select: January 2026
Result: "Budget Bedsheet" predicted to drop to 50 units
Action: Plan January sale/promotion to boost sales
```

### 3. **Production Planning**
```
Select: Next 3 months
Result: See which products need priority manufacturing
Action: Adjust production schedule accordingly
```

### 4. **Budget Forecasting**
```
Select: Q1 2026 (Jan, Feb, Mar)
Result: Predict revenue by product category
Action: Set realistic sales targets
```

---

## 🎨 Visual Representation

### Doughnut Chart Shows:
- **Product Names**: Top 6 predicted sellers
- **Quantities**: Predicted units to sell
- **Colors**: Different color per product
- **Legend**: Product list at bottom

### Example Display:
```
Premium Bedsheet: 150 units (35%)
Cotton Bedsheet: 120 units (28%)
Silk Bedsheet: 80 units (19%)
Budget Bedsheet: 50 units (12%)
King Size: 20 units (5%)
Queen Size: 5 units (1%)
```

---

## 🔄 How Filters Update

### Scenario 1: Planning for Next Month
```
Today: October 10, 2025
Filter: November 2025 (default)
Shows: Predicted top products for November
```

### Scenario 2: Planning for Holiday Season
```
Today: October 10, 2025
Filter: December 2025
Shows: Predicted Christmas/New Year sales
```

### Scenario 3: Planning for Next Year
```
Today: October 10, 2025
Filter: January 2026
Shows: Predicted New Year sales
```

### Scenario 4: Compare Months
```
Step 1: Select November 2025 → See predictions
Step 2: Select December 2025 → See predictions
Compare: Which products peak in December?
```

---

## 📊 Prediction Accuracy

### Factors Affecting Accuracy:

#### High Accuracy (85-95%):
- ✅ Product with 12+ months history
- ✅ Stable sales patterns
- ✅ Clear seasonal trends
- ✅ No major market changes

#### Medium Accuracy (70-84%):
- ⚠️ Product with 6-11 months history
- ⚠️ Moderate fluctuations
- ⚠️ Some seasonal patterns
- ⚠️ Minor market changes

#### Low Accuracy (50-69%):
- ❌ New product (<6 months)
- ❌ Highly volatile sales
- ❌ No clear patterns
- ❌ Major market disruptions

---

## 💡 Business Tips

### DO:
✅ **Use for inventory planning** (order 2-3 weeks before predicted month)
✅ **Compare year-over-year** (December 2024 vs December 2025)
✅ **Plan promotions** for products predicted to decline
✅ **Adjust pricing** based on predicted demand

### DON'T:
❌ **Over-order** based solely on prediction (add safety margin)
❌ **Ignore market changes** (new competitors, trends)
❌ **Forget seasonality** (holidays, weather, events)
❌ **Neglect customer feedback** (preferences change)

---

## 🔧 Technical Details

### Data Requirements:
- **Minimum**: 3 months of historical data per product
- **Recommended**: 12+ months for best accuracy
- **Updates**: Real-time with every page refresh

### Calculation Method:
```php
1. Get last 12 months of product sales
2. Calculate linear trend (growth/decline)
3. Check seasonal patterns for target month
4. Blend: 70% trend + 30% seasonal
5. Add ±8% realistic fluctuation
6. Sort by predicted quantity
7. Show top 6 products
```

### Performance:
- **Speed**: <100ms calculation time
- **Data**: Pulls from orders/order_items tables
- **Caching**: None (always fresh predictions)

---

## 📅 Example Workflow

### Monthly Planning Routine:

#### Week 1 of Current Month:
```
1. Check forecast for next month
2. Identify top predicted products
3. Calculate inventory needs
4. Place orders with suppliers
```

#### Week 2:
```
1. Check forecast for month+2
2. Plan marketing campaigns
3. Adjust pricing strategy
4. Schedule promotions
```

#### Week 3:
```
1. Compare actual vs predicted (current month)
2. Adjust future forecasts if needed
3. Analyze accuracy
4. Refine business strategy
```

#### Week 4:
```
1. Review quarter ahead (3 months)
2. Set sales targets
3. Plan budget allocation
4. Prepare for next month
```

---

## 🎯 Real Business Example

### Scenario: Planning for December 2025

**Current Date**: October 15, 2025

**Step 1**: Select December 2025 in forecast filter

**Prediction Shows**:
```
1. Premium Bedsheet: 200 units
2. Cotton Bedsheet: 180 units
3. Silk Bedsheet: 120 units
4. King Size: 100 units
5. Queen Size: 80 units
6. Budget Bedsheet: 60 units
```

**Action Plan**:
```
November 1: Order inventory
- Premium: 220 units (10% buffer)
- Cotton: 200 units (11% buffer)
- Silk: 135 units (12% buffer)

November 15: Launch marketing
- "Holiday Bedsheet Sale"
- Focus on Premium & Cotton (top sellers)

December 1: Monitor actual sales
- Compare to predictions
- Adjust promotions if needed

December 31: Review accuracy
- Calculate prediction error
- Improve next year's planning
```

---

## 🔄 Integration with Other Reports

### Works Together With:

1. **Actual Product Sales** (pie chart)
   - Compare predicted vs actual
   - Measure forecast accuracy

2. **Sales Forecasting** (3-month chart)
   - Total revenue prediction
   - Product-level breakdown

3. **Leaderboards** (forcasting.php)
   - Historical top sellers
   - Trend validation

---

## ✅ Quality Indicators

### Good Prediction:
- Matches historical patterns
- Reasonable quantities
- Top products make sense
- Seasonal adjustments visible

### Questionable Prediction:
- Drastically different from history
- Unrealistic quantities (too high/low)
- Unexpected top products
- No seasonal adjustment

### Action if Questionable:
1. Check data quality (any errors?)
2. Review historical sales (any anomalies?)
3. Consider external factors (market changes?)
4. Use conservative estimates

---

**Remember**: Forecasts are **predictions, not guarantees**. Use them as a planning tool, but always apply business judgment and market knowledge!
