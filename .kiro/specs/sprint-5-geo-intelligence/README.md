# Sprint 5: GEO & Intelligence Engine

**Feature Status:** Requirements Phase  
**Current Phase:** Requirements Definition  
**Sprint:** Sprint 5  
**Created:** 2026-05-22

---

## Quick Navigation

📋 **[Requirements Document](./requirements.md)** - Functional and technical requirements  
🎨 **[Design Document](./design.md)** - Architecture and implementation design  
✅ **[Tasks Document](./tasks.md)** - Implementation breakdown

---

## Feature Overview

Transform DODO AI SEO from "AI blog writer" to **"AI-native SEO + GEO intelligence platform"**.

### Strategic Shift

**From:** Classic SEO content generator  
**To:** AI Overview + LLM retrieval optimization platform

### Core Objectives

- **AI Overview Uyumlu İçerik** - Content optimized for Google AI Overviews
- **ChatGPT / Gemini / Claude Retrieval** - LLM-friendly content structure
- **Topical Authority Intelligence** - Semantic cluster mapping
- **Humanized AI Writing** - Natural, non-robotic content
- **Production Publishing Workflows** - Enterprise-grade content pipeline

---

## 10-Phase Implementation

### Phase 1: GEO / AI Visibility Engine
**Class:** `DODO_GEO_Engine`  
**Purpose:** Analyze content for AI system retrievability

**Analysis Metrics:**
- Answerability score
- Citation potential
- Chunk quality
- Entity richness
- Semantic clarity
- Retrieval friendliness
- Passage extraction suitability
- AI Overview compatibility
- Conversational intent match
- Featured snippet suitability

**Output:** 0-100 GEO score with actionable recommendations

---

### Phase 2: AI Answer Block Engine
**Integration:** Content generation pipeline  
**Purpose:** Auto-generate LLM-friendly content blocks

**Block Types:**
- Short answer blocks
- Featured snippet blocks
- Direct answer paragraphs
- Comparison sections
- AI summary sections
- FAQ intent clusters

**Goal:** Make content easily extractable by LLMs

---

### Phase 3: Content Intelligence Engine
**Class:** `DODO_Content_Intelligence`  
**Purpose:** Site-wide content intelligence

**Features:**

1. **Topic Map Builder**
   - Pillar topics
   - Support topics
   - Semantic clusters
   - Missing topics

2. **Cannibalization Detector**
   - Overlap detection
   - Duplicate intent
   - Weak differentiation

3. **Internal Link Intelligence**
   - Semantic link suggestions
   - Anchor suggestions
   - Orphan detection

4. **Topical Authority Tracking**
   - Cluster completeness
   - Authority score
   - Semantic coverage

---

### Phase 4: Humanization Engine
**Class:** `DODO_Humanizer`  
**Purpose:** Transform robotic AI content to natural writing

**Analysis:**
- Robotic phrasing detection
- Repetitive sentence structure
- Burstiness analysis
- Transition quality
- Conversational balance
- Paragraph rhythm

**Presets:**
- Human-like
- Editorial
- Technical
- Founder Voice
- Conversion Focused

**Approach:** Controlled humanization, not complete rewrite

---

### Phase 5: Production Publishing Engine
**Class:** `DODO_Publishing_Pipeline`  
**Purpose:** Enterprise content publishing workflow

**Features:**

1. **Scheduled Publishing**
   - Queue scheduling
   - Category scheduling
   - Publish windows

2. **Smart Review Queue**
   - Review required
   - Auto publish
   - Approval flow

3. **Content Memory**
   - Brand tone
   - CTA style
   - Forbidden words
   - Preferred entities
   - Internal link behavior

4. **Multi-Site Readiness**
   - Multiple domains
   - Multiple brands
   - Centralized content memory

---

### Phase 6: Analytics & GEO Reporting
**Integration:** Analytics Dashboard  
**Purpose:** Track GEO and intelligence metrics

**New Metrics:**
- GEO score
- AI visibility score
- Retrieval score
- Authority growth
- Entity coverage
- Internal link health
- Answerability score
- Humanization score

**Trend System:**
- Historical scores
- Growth graphs
- Improvement tracking

---

### Phase 7: Architecture Rules
**Purpose:** Maintain system integrity

**New Classes:**
- `class-dodo-geo-engine.php`
- `class-dodo-content-intelligence.php`
- `class-dodo-humanizer.php`
- `class-dodo-publishing-pipeline.php`

**Requirements:**
- Dependency injection compatible
- Logger support
- Queue system integration
- Token optimizer integration
- Migration-safe

---

### Phase 8: Performance & Safety
**Purpose:** Production-grade reliability

**Requirements:**
- No timeout on large content
- Chunk processing
- Graceful API failure fallback
- Token cost optimization
- Retry mechanism

---

### Phase 9: UI Integration
**Purpose:** Seamless user experience

**Principles:**
- Use existing design system
- Minimal UI additions
- No dashboard overload
- Tooltip + helper text
- Avoid 50 separate panels

---

### Phase 10: Final Validation
**Purpose:** Production readiness

**Checklist:**
- PHP syntax validation
- JS syntax validation
- CSS scope validation
- AJAX security (nonce)
- Capability checks
- DB migration safety
- Queue integration
- Responsive layout

---

## Design Philosophy

**Inspiration:** Clearscope + Surfer SEO + Frase + MarketMuse

**Core Principles:**
- Modular development
- Class-based architecture
- Production-safe
- No heavy JS frameworks
- WordPress native patterns
- Preserve existing system
- No UI redesign
- Backend architecture intact

---

## Technical Constraints

### Must Preserve
- ✅ Existing system functionality
- ✅ Current design system
- ✅ Backend architecture
- ✅ WordPress native structure
- ✅ All Sprint 1-4 features

### Must Avoid
- ❌ Breaking changes
- ❌ UI redesign
- ❌ Heavy JS frameworks
- ❌ Non-WordPress patterns
- ❌ Syntax errors
- ❌ Security vulnerabilities

---

## Integration Points

**Extends Existing Systems:**
- Content Generator (Phase 2)
- Content Improver (Phase 4)
- Analytics Dashboard (Phase 6)
- Job Queue (All phases)
- Token Optimizer (All phases)
- Error Handler (All phases)

**New Database Tables:**
- `dodo_geo_scores`
- `dodo_content_intelligence`
- `dodo_humanization_analysis`
- `dodo_publishing_pipeline`

---

## Success Metrics

**GEO Performance:**
- 70%+ average GEO score
- 80%+ AI visibility score
- 90%+ retrieval friendliness

**Content Quality:**
- 60%+ humanization score
- 50% reduction in robotic phrasing
- 80%+ conversational balance

**Publishing Efficiency:**
- 100% scheduled publish success
- 90%+ content memory accuracy
- 50% reduction in manual review time

**System Performance:**
- < 5s GEO analysis time
- < 3s humanization analysis
- 70%+ cache hit rate
- Zero timeout errors

---

## Current Status

### Phase: Requirements Definition

**Next Steps:**
1. Complete requirements document
2. Create design document
3. Create tasks document
4. Begin implementation

**Estimated Timeline:** 4-6 weeks

---

## Questions?

For questions or clarifications, refer to:
- [Requirements Document](./requirements.md) - Detailed specifications
- [Design Document](./design.md) - Architecture and implementation
- [Tasks Document](./tasks.md) - Implementation breakdown

---

**Last Updated:** 2026-05-22  
**Status:** Requirements Phase - In Progress 🚧
