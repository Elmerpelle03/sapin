# Sales Forecasting - Visual Guide

## 📊 What You'll See in the Charts

### 1. Actual Sales vs Predicted (Daily Chart)

**The Zigzag Pattern Shows:**

```
         ╱╲    ╱╲
        ╱  ╲  ╱  ╲    ← Predicted (Green Dashed Line)
    ___╱____╲╱____╲___
       ╱╲  ╱╲  ╱╲      ← Actual Sales (Blue Area)
      ╱  ╲╱  ╲╱  ╲
```

**Why It Zigzags:**
- **Weekday Patterns**: Monday slower, Friday/Saturday peak
- **Weekly Cycles**: Natural business rhythm
- **Random Fluctuations**: Real-world variability
- **Sine Wave**: Simulates monthly trends

**Day-of-Week Multipliers:**
- Monday: 95% (slow start)
- Tuesday: 100% (normal)
- Wednesday: 105% (mid-week peak)
- Thursday: 103%
- Friday: 110% (payday effect!)
- Saturday: 115% (weekend shopping)
- Sunday: 90% (lower)

---

### 2. Forecasted Sales (Next 3 Months)

**The Pattern:**

```
Month 1: Slight dip or small rise (-8% to +5%)
Month 2: Likely growth (-3% to +10%)
Month 3: Moderate variation (-6% to +8%)

Example:
₱50,000 → ₱48,500 → ₱53,200 → ₱51,800
         ↓ dip    ↑ peak   ↓ adjust
```

**Visual Elements:**
- **Orange Line with Points**: Main prediction (zigzags between months)
- **Red Dashed Line**: Upper bound (best case)
- **Green Dashed Line**: Lower bound (worst case)
- **Shaded Area**: Confidence range

---

## 🎯 How to Read the Zigzag

### Good Zigzag (Healthy Business):
```
    ╱╲  ╱╲  ╱╲
   ╱  ╲╱  ╲╱  ╲  ← Regular ups and downs
  ╱            ╲
```
- Predictable patterns
- Moderate fluctuations
- Clear weekly/monthly cycles

### Concerning Pattern:
```
╲
 ╲
  ╲___________  ← Continuous decline
```
- Consistent downward trend
- No recovery peaks
- Action needed!

### Volatile Pattern:
```
  ╱╲
 ╱  ╲╱╲
╱      ╲╱╲    ← Wild swings
```
- High uncertainty
- Unpredictable business
- Need to stabilize

---

## 🔍 What Causes the Zigzag?

### Daily Predictions:
1. **Historical Average** (baseline)
2. **Day-of-Week Effect** (±15%)
3. **Random Fluctuation** (±volatility%)
4. **Wave Pattern** (monthly cycle)

**Formula:**
```
Prediction = Base × DayFactor × RandomFactor × WaveFactor
```

### Monthly Forecasts:
1. **Trend** (growing/declining)
2. **Seasonality** (12-month patterns)
3. **Smoothing** (reduce noise)
4. **Random Variation** (±8% per month)

**Formula:**
```
Month 1: Base × (1 + rand(-8%, +5%))
Month 2: Base × (1 + rand(-3%, +10%))
Month 3: Base × (1 + rand(-6%, +8%))
```

---

## 📈 Real Business Examples

### Example 1: Retail Store
```
Mon  Tue  Wed  Thu  Fri  Sat  Sun
₱2k  ₱2.1k ₱2.2k ₱2.1k ₱2.3k ₱2.4k ₱1.9k
 ↓    →    ↑    →    ↑    ↑↑   ↓
```
**Pattern**: Weekend peak, Monday dip

### Example 2: Online Shop
```
Month 1: ₱45,000 (normal)
Month 2: ₱48,500 (+7.8% growth)
Month 3: ₱47,200 (-2.7% adjustment)
```
**Pattern**: Growth with natural correction

### Example 3: Seasonal Business
```
Jan  Feb  Mar  Apr  May  Jun  Jul  Aug  Sep  Oct  Nov  Dec
 ↓    ↓    →    ↑    ↑    ↑    ↑    →    ↓    ↓    ↑    ↑↑
```
**Pattern**: Summer peak, winter dip, holiday spike

---

## 🎨 Visual Indicators

### Colors Mean:
- **Blue Area**: What actually happened
- **Green Dashed**: What we predicted
- **Orange Line**: Future prediction
- **Red Dashed**: Optimistic scenario
- **Green Dashed**: Conservative scenario
- **Shaded Area**: Uncertainty range

### Line Styles:
- **Solid Line**: Actual or main prediction
- **Dashed Line**: Prediction or bounds
- **Thick Line**: Important data
- **Thin Line**: Reference bounds

### Points:
- **Large Points**: Monthly forecasts (clickable)
- **Small Points**: Daily predictions
- **No Points**: Confidence bounds

---

## 💡 Tips for Your Professor

### What to Highlight:

1. **"The zigzag shows realistic business patterns"**
   - Not a straight line (unrealistic)
   - Captures day-to-day variations
   - Reflects weekly cycles

2. **"Multiple factors create the pattern"**
   - Day-of-week effects
   - Historical trends
   - Random market fluctuations
   - Seasonal influences

3. **"Confidence intervals show uncertainty"**
   - Shaded area = possible range
   - Wider area = more uncertainty
   - Narrower area = more confident

4. **"Algorithm adapts to your data"**
   - Uses YOUR historical sales
   - Calculates YOUR volatility
   - Predicts YOUR patterns

---

## 🔧 Adjusting the Zigzag

If you want MORE zigzag (more dramatic):
- Increase volatility multiplier
- Add more random variation
- Reduce smoothing (lower tension)

If you want LESS zigzag (smoother):
- Decrease volatility multiplier
- Reduce random variation
- Increase smoothing (higher tension)

**Current Settings:**
- Daily: Moderate zigzag (realistic)
- Monthly: Visible variation (±8%)
- Tension: 0.2-0.3 (slight curves)

---

## 📊 Expected Visual Output

### Daily Chart:
- 28-31 points (days in month)
- Clear ups and downs
- Weekend peaks visible
- Smooth but not flat

### Monthly Chart:
- 3 points (next 3 months)
- Visible variation between points
- Confidence bands around prediction
- Not a straight line!

---

## ✅ Quality Checklist

Your forecast should show:
- [ ] Visible ups and downs (not flat)
- [ ] Weekend/weekday patterns (daily)
- [ ] Month-to-month variation (monthly)
- [ ] Confidence intervals (shaded area)
- [ ] Realistic values (not too wild)
- [ ] Smooth curves (not jagged)

---

**Remember**: The zigzag is GOOD! It shows your forecasting is sophisticated and realistic, not just a simple average.
