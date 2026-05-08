# Spin To Win — Understanding Prize Weights

This document explains how the **Weight** field on each prize segment controls the probability of that prize being won. It is written for clients who configure their own spin wheels in WooCommerce.

---

## What is a weight?

Each prize segment has a **Weight** number (set in the Spin To Win tab on a competition product). Weights are **relative** — they do not need to add up to 100 or any other specific total.

> **Key idea:** The probability of winning a prize = `this prize's weight ÷ total of all weights`.

---

## The formula

```
Probability of Prize A = Weight(A) / (Weight(A) + Weight(B) + Weight(C) + ...)
```

### Example — three prize segments

| Prize | Weight | Probability |
|-------|--------|-------------|
| £5 Site Credit | 50 | 50 / 100 = **50%** |
| £20 Site Credit | 30 | 30 / 100 = **30%** |
| Headphones | 20 | 20 / 100 = **20%** |
| **Total** | **100** | **100%** |

You could use the exact same weights as `5`, `3`, `2` — the result would be identical (50%, 30%, 20%) because only the *ratio* matters.

---

## "No Win" segments

A **No Win** segment (type = No Win) works exactly like any other segment. If you want a 40% chance of no win:

| Segment | Weight | Probability |
|---------|--------|-------------|
| No Win | 40 | 40% |
| £5 Credit | 35 | 35% |
| £10 Credit | 25 | 25% |

---

## Disabling a prize without deleting it

Each segment has an **Enabled** checkbox in the editor. Untick it to take the prize out of the draw without losing its label, weight, image, or stock. The remaining weights re-normalise automatically — exactly like an out-of-stock physical prize. Tick it again to bring the prize back. This is the right way to pause a prize between draws or rotate stock; do **not** use a weight of 0 for this.

---

## Out-of-stock prizes are excluded automatically

Physical prizes (items with a stock count) are **removed from the draw** when their stock reaches zero. The remaining weights re-normalise automatically — you do not need to change any weights.

### Example

| Prize | Weight | Normal % | When Headphones sold out |
|-------|--------|----------|--------------------------|
| £5 Credit | 50 | 50% | 50 / 80 = **62.5%** |
| £20 Credit | 30 | 30% | 30 / 80 = **37.5%** |
| Headphones (stock: 0) | 20 | 20% | *excluded* |

The wheel still works with just the remaining eligible segments.

---

## Setting up a "1 in X chance" grand prize

To give a grand prize a 1-in-50 chance (2%):

1. Decide the total of all weights (e.g. **1000** — makes the maths easy).
2. Grand prize weight = 1% of 1000 = **10** (giving 10/1000 = 1%).
3. Distribute the remaining 990 weight among other prizes or No Win segments.

| Segment | Weight |
|---------|--------|
| Grand Prize | 10 |
| No Win | 600 |
| £5 Credit | 250 |
| £2 Credit | 140 |
| **Total** | **1000** |

Grand prize probability = 10 / 1000 = **1%**.

---

## Making all prizes equally likely

Set the **same weight** on every segment:

| Segment | Weight | Probability |
|---------|--------|-------------|
| Prize A | 10 | 33.3% |
| Prize B | 10 | 33.3% |
| Prize C | 10 | 33.3% |

Any number works as long as all weights are equal.

---

## Common mistakes

| Mistake | What actually happens |
|---------|----------------------|
| Trying to disable a prize by setting its weight to 0 | The editor blocks values below 0.0001, and any weight that does slip through is clamped to 0.0001 internally — so the segment can still (very rarely) win. **To temporarily turn off a prize, untick its "Enabled" checkbox** in the segment row. To remove it permanently, delete the row. |
| Thinking weights are percentages | Weight 80 does **not** mean 80%. If the only other prize has weight 80 too, each prize is 50%. |
| Leaving stock at 0 and expecting the prize to win | A physical prize with 0 stock is silently excluded from every draw. The probability shown in your weight calculation will not match reality once stock is exhausted. |
| Changing weights mid-competition | Changing weights takes effect immediately for all future spins. This is intentional — you can rebalance prizes as the competition progresses. |

---

## How the algorithm works (technical reference)

The system uses a **cumulative weighted random pick**:

1. Filter out physical prizes with zero stock.
2. Sum all remaining weights → `total_weight`.
3. Pick a random number `r` between 0 and `total_weight`.
4. Walk through the segments in order, adding each weight to a running total. Stop at the first segment where the running total exceeds `r`. That segment wins.

This is equivalent to dividing a line of length `total_weight` into sections proportional to each segment's weight and throwing a dart at a random point.

Source: `wp-content/plugins/nera-spin-to-win/includes/class-spin-service.php`, method `weighted_pick_index()`.

---

## Quick reference checklist for setting up a wheel

- [ ] Every segment has a weight > 0.
- [ ] Every segment that should be in the draw has its **Enabled** checkbox ticked.
- [ ] Physical prize segments have a stock count set.
- [ ] Check: sum all weights, divide each by the total. Do the resulting percentages match your intent?
- [ ] "No Win" segments are included if you want some spins to be non-winners.
- [ ] Grand prizes have a much lower weight than common prizes.
