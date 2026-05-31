# Sprint 2: Content Intelligence Layer - Tasks

**Feature Name:** Content Intelligence Layer  
**Sprint:** Sprint 2  
**Phase:** Tasks & Implementation  
**Created:** 2026-05-21  
**Version:** 1.0.0

---

## TASK OVERVIEW

### Sprint Summary

**Total Tasks:** 12  
**Estimated Duration:** 2-3 weeks  
**Implementation Order:** Sequential with some parallel opportunities

**Task Categories:**
- **Backend Core (Tasks 1-7):** 7 tasks - Intelligence engines and scoring
- **Frontend UI (Task 8):** 1 task - Intelligence panel interface
- **UX Integration (Task 9):** 1 task - Live analysis experience
- **Infrastructure (Tasks 10-11):** 2 tasks - Caching and history
- **Documentation (Task 12):** 1 task - Explanations and help

### Implementation Strategy

**Phase 1: Foundation (Tasks 1-2)**
- Build core scoring engines
- Establish baseline intelligence

**Phase 2: Intelligence (Tasks 3-7)**
- Add advanced analysis features
- Build recommendation engine

**Phase 3: UI/UX (Tasks 8-9)**
- Create intelligence panel
- Integrate live feedback

**Phase 4: Infrastructure (Tasks 10-11)**
- Implement caching
- Add history tracking

**Phase 5: Polish (Task 12)**
- Add explanations
- Complete documentation

---

## TASK 1: Content Health Score Engine

**Priority:** CRITICAL  
**Complexity:** HIGH  
**Estimated Time:** 3-4 days  
**Dependencies:** None  
**Requirement:** REQ-2.1

### 1.1 Objective

Create comprehensive 0-100 scoring system that evaluates content across 5 dimensions: SEO, Quality, Readability, Semantic, and AI Risk.

### 1.2 Implementation Steps

**Step 1: Create Score Calculator Class**
```php
File: includes/class-dodo-score-calculator.php

Class: DODO_Score_Calculator
Methods:
- calculate_health_score($intelligence)
- calculate_seo_score($content, $keyword, $metadata)
- calculate_quality_score($content)
- calculate_readability_score($content)
- calculate_semantic_score($content, $keyword)
- calculate_ai_risk_score($content)
```

**Step 2: Implement SEO Score (25% weight)**
- Focus keyword presence (15 points)
- SEO title optimization (10 points)
- Meta description quality (10 points)
- Slug optimization (10 points)
- Keyword in title (15 points)
- Keyword in description (15 points)
- Keyword in first paragraph (15 points)
- Description length (10 points)

**Step 3: Implement Quality Score (30% weight)**
- Word count graduated scoring (25 points)
- H2 heading count (20 points)
- H3 heading count (15 points)
- Paragraph balance (15 points)
- FAQ section presence (10 points)
- CTA presence (10 points)
- Penalties for spam/imbalance (-10 points max)


**Step 4: Implement Readability Score (20% weight)**
- Sentence length analysis
- Sentence variation
- Paragraph consistency
- Transition word ratio
- Passive voice detection
- Complex word density
- Flesch Reading Ease

**Step 5: Implement Semantic Score (15% weight)**
- Topic coherence (via OpenAI)
- Keyword relevance
- LSI keyword presence
- Content flow
- Contextual depth

**Step 6: Implement AI Risk Score (10% weight)**
- Repetitive patterns (25 points)
- AI signature phrases (30 points)
- Unnatural transitions (20 points)
- Perfect structure detection (15 points)
- Personality absence (10 points)

**Step 7: Implement Health Score Formula**
```php
Health = (SEO × 0.25) + (Quality × 0.30) + (Readability × 0.20) + 
         (Semantic × 0.15) + ((100 - AI_Risk) × 0.10)
```

**Step 8: Add Helper Methods**
- extract_sentences()
- extract_paragraphs()
- calculate_word_count()
- detect_headings()
- calculate_keyword_density()
- detect_faq_section()
- detect_cta()

### 1.3 Acceptance Criteria

- [ ] All 5 dimension scores calculate correctly
- [ ] Health score formula implemented
- [ ] Score ranges: 0-100 for all dimensions
- [ ] Score calculation < 500ms
- [ ] Unit tests for each scoring method
- [ ] Edge cases handled (empty content, very long content)
- [ ] Scores calibrated with 20+ real posts
- [ ] Documentation complete

### 1.4 Testing Requirements

**Unit Tests:**
```php
test_calculate_health_score_excellent()
test_calculate_health_score_good()
test_calculate_health_score_fair()
test_calculate_health_score_poor()
test_calculate_seo_score_perfect()
test_calculate_seo_score_missing_keyword()
test_calculate_quality_score_long_content()
test_calculate_quality_score_short_content()
test_calculate_readability_score()
test_calculate_ai_risk_score_high()
test_calculate_ai_risk_score_low()
```

**Integration Tests:**
- Test with real blog posts (10+ samples)
- Verify score consistency
- Test performance with large content
- Verify score ranges (0-100)

### 1.5 Files to Create/Modify

**New Files:**
- `includes/class-dodo-score-calculator.php` (500+ lines)

**Modified Files:**
- `includes/class-dodo-core.php` (add dependency)

### 1.6 Documentation

- [ ] PHPDoc comments for all methods
- [ ] Scoring methodology documented
- [ ] Score range explanations
- [ ] Calibration notes

---

## TASK 2: AI Confidence Engine

**Priority:** CRITICAL  
**Complexity:** HIGH  
**Estimated Time:** 2-3 days  
**Dependencies:** Task 1  
**Requirement:** REQ-2.2

### 2.1 Objective

Build confidence scoring system that evaluates AI's certainty in analysis and recommendations, with risk level assessment.

### 2.2 Implementation Steps

**Step 1: Create Confidence Analyzer Class**
```php
File: includes/class-dodo-confidence-analyzer.php

Class: DODO_Confidence_Analyzer
Methods:
- calculate_confidence($intelligence, $content, $metadata)
- assess_risk_level($confidence, $intelligence)
- identify_risk_factors($content, $intelligence)
- explain_confidence($confidence_data)
```

**Step 2: Implement Confidence Factors**

1. **Content Clarity (30%)**
   - Focus keyword defined (10 points)
   - Clear structure (10 points)
   - Clear sections (10 points)

2. **Analysis Certainty (25%)**
   - Clear SEO state (10 points)
   - Clear improvement opportunities (10 points)
   - Strong patterns detected (5 points)

3. **Improvement Feasibility (25%)**
   - Clear improvement path (10 points)
   - Low semantic risk (10 points)
   - Measurable impact (5 points)

4. **Context Completeness (20%)**
   - Sufficient word count (10 points)
   - Complete sections (5 points)
   - Complete metadata (5 points)

**Step 3: Implement Risk Level Assessment**
```php
Risk Levels:
- Low: confidence >= 75 AND no critical issues
- Medium: confidence 50-74 OR some issues
- High: confidence < 50 OR critical issues
```

**Step 4: Implement Risk Factor Detection**
- Insufficient content
- Missing critical metadata
- Unclear structure
- High AI risk score
- Low semantic quality
- Conflicting signals

**Step 5: Add Confidence Explanation**
- Why confidence is high/low
- What factors contribute
- What would increase confidence
- What uncertainties exist

### 2.3 Acceptance Criteria

- [ ] Confidence score (0-100%) calculated
- [ ] Risk level (low/medium/high) determined
- [ ] Risk factors identified
- [ ] Confidence explanation generated
- [ ] Calculation < 200ms
- [ ] Unit tests complete
- [ ] Integration with Task 1
- [ ] Documentation complete

### 2.4 Testing Requirements

**Unit Tests:**
```php
test_calculate_confidence_high()
test_calculate_confidence_medium()
test_calculate_confidence_low()
test_assess_risk_level_low()
test_assess_risk_level_medium()
test_assess_risk_level_high()
test_identify_risk_factors()
test_explain_confidence()
```

### 2.5 Files to Create/Modify

**New Files:**
- `includes/class-dodo-confidence-analyzer.php` (300+ lines)

**Modified Files:**
- `includes/class-dodo-core.php` (add dependency)

### 2.6 Documentation

- [ ] Confidence calculation methodology
- [ ] Risk level criteria
- [ ] Risk factor definitions
- [ ] Usage examples

---


## TASK 3: Smart Recommendation Engine

**Priority:** HIGH  
**Complexity:** HIGH  
**Estimated Time:** 3-4 days  
**Dependencies:** Tasks 1, 2  
**Requirement:** REQ-2.3

### 3.1 Objective

Create context-aware recommendation system that generates prioritized, actionable improvement suggestions.

### 3.2 Implementation Steps

**Step 1: Create Recommendation Engine Class**
```php
File: includes/class-dodo-recommendation-engine.php

Class: DODO_Recommendation_Engine
Methods:
- generate_recommendations($intelligence, $content, $metadata)
- prioritize_recommendations($recommendations)
- calculate_impact($recommendation, $intelligence)
- calculate_effort($recommendation)
- format_recommendation($recommendation)
```

**Step 2: Implement Recommendation Types**

**Critical Recommendations (Red):**
- Missing focus keyword in title
- No meta description
- Content too short (< 500 words)
- No heading structure
- High AI detection risk (> 60)
- Broken content flow

**Important Recommendations (Yellow):**
- Keyword density issues
- Weak heading structure
- Missing FAQ section
- No CTA
- Readability issues
- Paragraph imbalance

**Suggested Improvements (Blue):**
- Add LSI keywords
- Improve transitions
- Enhance semantic depth
- Add internal links
- Optimize sentence variety
- Strengthen conclusion

**Step 3: Implement Prioritization Algorithm**
```php
Priority Score = (Impact × 2) + (10 - Effort)

Impact: 1-10 (expected score improvement)
Effort: 1-10 (implementation difficulty)

Sort by: Priority Score DESC
```

**Step 4: Implement Impact Calculation**
- Estimate score improvement per dimension
- Calculate weighted impact on health score
- Consider current score (more impact if low)
- Consider confidence level

**Step 5: Implement Effort Estimation**
- Low: Simple text changes, < 5 minutes
- Medium: Structural changes, 5-15 minutes
- High: Major rewrites, > 15 minutes

**Step 6: Format Recommendations**
```php
Recommendation Format:
{
  "id": "unique_id",
  "type": "critical|important|suggested",
  "category": "seo|content|readability|semantic|ai-risk",
  "title": "Short actionable title",
  "description": "Detailed explanation",
  "impact": {
    "dimension": "seo",
    "points": 8,
    "description": "+8 SEO score"
  },
  "effort": "low|medium|high",
  "priority": 1-10,
  "actionable": true|false,
  "auto_fixable": true|false,
  "action_data": {...}
}
```

### 3.3 Acceptance Criteria

- [ ] Recommendations generated for all score dimensions
- [ ] Recommendations prioritized correctly
- [ ] Impact calculated accurately
- [ ] Effort estimated reasonably
- [ ] Top 10 recommendations returned
- [ ] Recommendations actionable (not generic)
- [ ] Generation < 300ms
- [ ] Unit tests complete
- [ ] Documentation complete

### 3.4 Testing Requirements

**Unit Tests:**
```php
test_generate_recommendations_critical()
test_generate_recommendations_important()
test_generate_recommendations_suggested()
test_prioritize_recommendations()
test_calculate_impact()
test_calculate_effort()
test_format_recommendation()
```

**Integration Tests:**
- Test with various content types
- Verify recommendation relevance
- Test prioritization accuracy
- Verify impact estimates

### 3.5 Files to Create/Modify

**New Files:**
- `includes/class-dodo-recommendation-engine.php` (600+ lines)

**Modified Files:**
- `includes/class-dodo-core.php` (add dependency)

### 3.6 Documentation

- [ ] Recommendation types documented
- [ ] Prioritization algorithm explained
- [ ] Impact calculation methodology
- [ ] Effort estimation criteria

---

## TASK 4: Semantic Quality Analyzer

**Priority:** HIGH  
**Complexity:** MEDIUM  
**Estimated Time:** 2-3 days  
**Dependencies:** Task 1  
**Requirement:** REQ-2.4

### 3.1 Objective

Implement deep semantic analysis using OpenAI to understand content meaning beyond keywords.

### 3.2 Implementation Steps

**Step 1: Create Semantic Analyzer Class**
```php
File: includes/class-dodo-semantic-analyzer.php

Class: DODO_Semantic_Analyzer
Methods:
- analyze_semantic_quality($content, $keyword)
- analyze_topic_coherence($content)
- analyze_keyword_relevance($content, $keyword)
- analyze_content_flow($content)
- analyze_content_depth($content)
- identify_lsi_keywords($content, $keyword)
```

**Step 2: Implement OpenAI Integration**

**Semantic Analysis Prompt:**
```
Analyze this content for semantic quality:

Content: {content}
Focus Keyword: {keyword}

Provide analysis in JSON format:
{
  "topic_coherence": 0-100,
  "keyword_relevance": 0-100,
  "content_flow": 0-100,
  "content_depth": 0-100,
  "lsi_keywords": ["keyword1", "keyword2", ...],
  "topic_drift": true/false,
  "depth_level": "surface|moderate|substantial|comprehensive",
  "main_topics": ["topic1", "topic2", ...],
  "coherence_issues": ["issue1", "issue2", ...]
}
```

**Step 3: Implement Topic Coherence Analysis**
- Main topic identification
- Subtopic distribution
- Topic drift detection
- Thematic consistency

**Step 4: Implement Keyword Relevance Analysis**
- Focus keyword context appropriateness
- LSI keyword identification
- Semantic keyword variations
- Natural keyword integration

**Step 5: Implement Content Flow Analysis**
- Logical progression
- Section connectivity
- Transition quality
- Narrative coherence

**Step 6: Implement Content Depth Analysis**
- Information density
- Example usage
- Data/statistics presence
- Expert insights indicators

**Step 7: Add Caching**
- Cache semantic analysis results (30 min)
- Cache key: post_id + content_hash + keyword
- Fallback to rule-based if API fails

### 3.3 Acceptance Criteria

- [ ] Semantic score (0-100) calculated
- [ ] Topic coherence measured
- [ ] LSI keywords identified
- [ ] Content depth assessed
- [ ] Flow quality evaluated
- [ ] OpenAI integration working
- [ ] Caching implemented
- [ ] Fallback for API failure
- [ ] Analysis < 2s
- [ ] Unit tests complete
- [ ] Documentation complete

### 3.4 Testing Requirements

**Unit Tests:**
```php
test_analyze_semantic_quality()
test_analyze_topic_coherence()
test_analyze_keyword_relevance()
test_analyze_content_flow()
test_analyze_content_depth()
test_identify_lsi_keywords()
test_cache_semantic_analysis()
test_fallback_on_api_failure()
```

### 3.5 Files to Create/Modify

**New Files:**
- `includes/class-dodo-semantic-analyzer.php` (400+ lines)

**Modified Files:**
- `includes/class-dodo-core.php` (add dependency)
- `includes/class-dodo-openai.php` (add semantic analysis method)

### 3.6 Documentation

- [ ] Semantic analysis methodology
- [ ] OpenAI prompt documentation
- [ ] LSI keyword explanation
- [ ] Depth level criteria

---


## TASK 5: Editorial Style Detection

**Priority:** MEDIUM  
**Complexity:** MEDIUM  
**Estimated Time:** 2 days  
**Dependencies:** Task 4  
**Requirement:** REQ-2.5

### 5.1 Objective

Detect and analyze editorial writing style to maintain consistency and authenticity.

### 5.2 Implementation Steps

**Step 1: Create Style Detector Class**
```php
File: includes/class-dodo-style-detector.php

Class: DODO_Style_Detector
Methods:
- detect_style($content)
- detect_tone($content)
- detect_voice($content)
- detect_personality($content)
- measure_consistency($content)
```

**Step 2: Implement OpenAI Style Detection**

**Style Detection Prompt:**
```
Detect the editorial style of this content:

Content: {content}

Provide analysis in JSON format:
{
  "tone": "formal|informal|professional|casual|technical|conversational",
  "voice": "first_person|second_person|third_person|mixed",
  "voice_ratio": {"first": 0.2, "second": 0.5, "third": 0.3},
  "personality_score": 0-100,
  "consistency_score": 0-100,
  "unique_phrases": ["phrase1", "phrase2", ...],
  "style_profile": "professional_editorial|conversational_blog|technical_guide|personal_story",
  "style_issues": ["issue1", "issue2", ...]
}
```

**Step 3: Implement Tone Detection**
- Formal vs Informal
- Professional vs Casual
- Technical vs Accessible
- Authoritative vs Conversational

**Step 4: Implement Voice Analysis**
- First/Second/Third person detection
- Voice ratio calculation
- Direct vs Indirect communication
- Personal vs Impersonal style

**Step 5: Implement Personality Detection**
- Unique phrases identification
- Writing quirks
- Humor usage
- Emotional language

**Step 6: Implement Consistency Measurement**
- Tone consistency across sections
- Voice consistency
- Style drift detection
- Brand voice alignment

### 5.3 Acceptance Criteria

- [ ] Style profile detected
- [ ] Tone identified
- [ ] Voice ratio calculated
- [ ] Personality score measured
- [ ] Consistency score calculated
- [ ] Style issues identified
- [ ] OpenAI integration working
- [ ] Analysis < 1.5s
- [ ] Unit tests complete
- [ ] Documentation complete

### 5.4 Files to Create/Modify

**New Files:**
- `includes/class-dodo-style-detector.php` (300+ lines)

**Modified Files:**
- `includes/class-dodo-core.php` (add dependency)

---

## TASK 6: Content Depth Analysis

**Priority:** MEDIUM  
**Complexity:** MEDIUM  
**Estimated Time:** 2 days  
**Dependencies:** Task 4  
**Requirement:** REQ-2.6

### 6.1 Objective

Measure content depth to distinguish between surface-level and comprehensive content.

### 6.2 Implementation Steps

**Step 1: Create Depth Analyzer Class**
```php
File: includes/class-dodo-depth-analyzer.php

Class: DODO_Depth_Analyzer
Methods:
- analyze_depth($content)
- measure_information_density($content)
- assess_topic_coverage($content)
- evaluate_explanation_quality($content)
- identify_value_addition($content)
```

**Step 2: Implement Information Density**
- Facts per paragraph
- Data/statistics presence
- Example usage frequency
- Citation/reference indicators

**Step 3: Implement Topic Coverage**
- Subtopic breadth
- Aspect completeness
- Question answering
- Edge case coverage

**Step 4: Implement Explanation Quality**
- Concept elaboration
- "Why" and "How" presence
- Step-by-step instructions
- Context provision

**Step 5: Implement Value Addition**
- Unique insights detection
- Expert perspective indicators
- Actionable takeaways
- Practical applications

**Step 6: Calculate Depth Score**
```php
Depth Levels:
- Comprehensive (90-100): Deep, detailed, expert-level
- Substantial (75-89): Good depth, covers main aspects
- Moderate (60-74): Basic coverage, some depth
- Surface (40-59): Shallow, lacks detail
- Minimal (0-39): Very basic, insufficient depth
```

### 6.3 Acceptance Criteria

- [ ] Depth score (0-100) calculated
- [ ] Information density measured
- [ ] Topic coverage assessed
- [ ] Explanation quality evaluated
- [ ] Value addition identified
- [ ] Depth level determined
- [ ] Analysis < 500ms
- [ ] Unit tests complete
- [ ] Documentation complete

### 6.4 Files to Create/Modify

**New Files:**
- `includes/class-dodo-depth-analyzer.php` (250+ lines)

**Modified Files:**
- `includes/class-dodo-core.php` (add dependency)

---

## TASK 7: AI Detection Risk Analyzer

**Priority:** HIGH  
**Complexity:** HIGH  
**Estimated Time:** 2-3 days  
**Dependencies:** Task 1  
**Requirement:** REQ-2.7

### 7.1 Objective

Analyze content for AI-generated patterns that could trigger AI detection tools.

### 7.2 Implementation Steps

**Step 1: Create AI Risk Detector Class**
```php
File: includes/class-dodo-ai-risk-detector.php

Class: DODO_AI_Risk_Detector
Methods:
- detect_ai_risk($content)
- detect_repetitive_patterns($content)
- detect_ai_phrases($content)
- detect_unnatural_transitions($content)
- detect_perfect_structure($content)
- detect_personality_absence($content)
```

**Step 2: Implement Repetitive Pattern Detection**
- Repeated sentence structures
- Repeated transition phrases
- Repeated paragraph openings
- Pattern frequency scoring

**Step 3: Implement AI Phrase Detection**
```php
AI Signature Phrases:
- "It's important to note that"
- "In today's digital landscape"
- "Delve into"
- "Tapestry of"
- "Realm of"
- "It's worth noting"
- "In conclusion"
- "Moreover", "Furthermore", "Additionally" (overuse)
```

**Step 4: Implement Unnatural Transition Detection**
- Generic transition phrase overuse
- Forced connectivity
- Robotic flow
- Mechanical transitions

**Step 5: Implement Perfect Structure Detection**
- Too perfect heading hierarchy
- Overly balanced paragraphs
- Mechanical consistency
- Lack of natural variation

**Step 6: Implement Personality Absence Detection**
- No unique voice
- No personal touches
- No humor or emotion
- Generic tone throughout

**Step 7: Calculate Risk Score**
```php
Risk Score = 
  Repetitive Patterns (0-25) +
  AI Phrases (0-30) +
  Unnatural Transitions (0-20) +
  Perfect Structure (0-15) +
  Personality Absence (0-10)

Risk Levels:
- Low (0-30): Natural, human-like writing
- Medium (31-60): Some AI patterns, needs humanization
- High (61-100): Strong AI indicators, major revision needed
```

### 7.3 Acceptance Criteria

- [ ] AI risk score (0-100) calculated
- [ ] Risk factors identified
- [ ] Specific AI patterns highlighted
- [ ] Humanization suggestions provided
- [ ] Risk level determined
- [ ] Analysis < 800ms
- [ ] Unit tests complete
- [ ] Documentation complete

### 7.4 Files to Create/Modify

**New Files:**
- `includes/class-dodo-ai-risk-detector.php` (400+ lines)

**Modified Files:**
- `includes/class-dodo-core.php` (add dependency)

---


## TASK 8: Content Insights Panel (UI)

**Priority:** CRITICAL  
**Complexity:** MEDIUM  
**Estimated Time:** 3-4 days  
**Dependencies:** Tasks 1-7  
**Requirement:** REQ-2.8

### 8.1 Objective

Create premium UI panel that displays content intelligence in an actionable, beautiful way.

### 8.2 Implementation Steps

**Step 1: Create Intelligence Panel HTML**
```php
File: admin/views/page-content-improver.php (modify)

Add intelligence panel to sidebar:
- Health Score Card
- Dimension Breakdown
- Confidence Indicator
- Recommendations List
- Quick Stats
- Intelligence Timeline
```

**Step 2: Create Intelligence Panel CSS**
```css
File: assets/css/intelligence-panel.css (new)

Components:
- .dodo-intelligence-panel
- .dodo-health-score-card
- .dodo-score-circle
- .dodo-dimension-breakdown
- .dodo-confidence-indicator
- .dodo-recommendations-list
- .dodo-quick-stats
- .dodo-intelligence-timeline
```

**Step 3: Create Intelligence Panel JavaScript**
```javascript
File: assets/js/intelligence-panel.js (new)

Module: DodoIntelligencePanel
Methods:
- init()
- updateIntelligence(intelligence)
- renderHealthScore(score)
- renderDimensions(dimensions)
- renderConfidence(confidence, risk)
- renderRecommendations(recommendations)
- renderQuickStats(stats)
- renderTimeline(history)
- animateScoreUpdate(oldScore, newScore)
```

**Step 4: Implement Health Score Card**
- Circular progress indicator (SVG)
- Score number with count-up animation
- Score label (Excellent/Good/Fair/Poor)
- Score trend indicator (↑↓→)
- Last analyzed timestamp
- "Improve Now" button

**Step 5: Implement Dimension Breakdown**
- 5 horizontal progress bars
- Each with label, score, and color
- Hover tooltips with details
- Click to expand full breakdown
- Stagger animation on load

**Step 6: Implement Confidence Indicator**
- Confidence percentage
- 10-dot confidence meter
- Risk level badge (Low/Medium/High)
- Explanation tooltip
- Color-coded by confidence level

**Step 7: Implement Recommendations List**
- Top 3-5 recommendations visible
- Type badges (Critical/Important/Suggested)
- Impact and effort indicators
- Apply and Learn More buttons
- "Show All" expandable
- Hover lift effect

**Step 8: Implement Quick Stats**
- 3-column grid (Words, Reading Time, Grade)
- Keyword density indicator
- AI risk level indicator
- Checkmarks for good values

**Step 9: Implement Intelligence Timeline**
- Line graph (SVG)
- Last 10 analyses
- Interactive points with tooltips
- Trend indicator
- Smooth line animation

**Step 10: Add Responsive Design**
- Desktop: Sticky sidebar
- Tablet: Condensed sidebar
- Mobile: Accordion sections

### 8.3 Acceptance Criteria

- [ ] Intelligence panel renders correctly
- [ ] All components display properly
- [ ] Circular progress animates smoothly
- [ ] Dimension bars fill with stagger
- [ ] Recommendations interactive
- [ ] Timeline graph functional
- [ ] Responsive on all devices
- [ ] Smooth animations (60fps)
- [ ] Accessible (WCAG 2.1 AA)
- [ ] Follows Sprint 1B design system
- [ ] No layout shifts
- [ ] Performance < 100ms render

### 8.4 Testing Requirements

**Visual Tests:**
- Test on Chrome, Firefox, Safari
- Test on desktop, tablet, mobile
- Test with different score ranges
- Test with varying recommendation counts
- Test timeline with different data points

**Interaction Tests:**
- Click recommendation apply button
- Click show more recommendations
- Hover timeline points
- Expand dimension details
- Test keyboard navigation

### 8.5 Files to Create/Modify

**New Files:**
- `assets/css/intelligence-panel.css` (800+ lines)
- `assets/js/intelligence-panel.js` (600+ lines)

**Modified Files:**
- `admin/views/page-content-improver.php` (add panel HTML)
- `includes/class-dodo-admin.php` (enqueue new assets)

### 8.6 Documentation

- [ ] Component documentation
- [ ] CSS class reference
- [ ] JavaScript API documentation
- [ ] Customization guide

---

## TASK 9: Live Analysis Experience (UX)

**Priority:** HIGH  
**Complexity:** MEDIUM  
**Estimated Time:** 2-3 days  
**Dependencies:** Task 8  
**Requirement:** REQ-2.9

### 9.1 Objective

Implement real-time intelligence updates as user makes improvements, creating live feedback loop.

### 9.2 Implementation Steps

**Step 1: Implement Real-Time Score Updates**
```javascript
File: assets/js/intelligence-panel.js (extend)

Features:
- Listen for improvement applied event
- Recalculate intelligence
- Animate score change
- Show score delta (+5, -2)
- Color transition if range changes
```

**Step 2: Implement Progressive Intelligence Loading**
- Stage 1: Health score (500ms)
- Stage 2: Dimensions (1s, stagger 100ms)
- Stage 3: Confidence (1.5s)
- Stage 4: Recommendations (2s)
- Stage 5: Stats and timeline (2.5s)

**Step 3: Implement Improvement Impact Preview**
```javascript
Before applying improvement:
- Show predicted score change
- Show expected impact
- Show confidence in prediction
- Show risk assessment
```

**Step 4: Implement Interactive Recommendations**
- Click to see details modal
- Click to apply recommendation
- Track recommendation status
- Show applied/pending/dismissed state
- Update recommendations after apply

**Step 5: Implement Intelligence Notifications**
```javascript
Toast notifications for:
- Score milestone reached (+10, +20, etc.)
- "Score improved by +10!" celebration
- "High risk detected" warning
- "Ready to publish" (score >= 90) confirmation
- "Excellent score!" (score >= 95) celebration
```

**Step 6: Add Loading States**
- Skeleton loading for initial analysis
- Shimmer effect during calculation
- Progress indicator for long operations
- Smooth transitions between states

**Step 7: Implement Score Delta Animation**
```javascript
When score changes:
1. Highlight score card (glow)
2. Animate number change (count-up/down)
3. Show delta indicator (+5)
4. Fade delta after 3s
5. Update progress ring
```

### 9.3 Acceptance Criteria

- [ ] Score updates in real-time after improvements
- [ ] Smooth animations throughout
- [ ] No UI blocking during updates
- [ ] Clear feedback for every action
- [ ] Milestone celebrations working
- [ ] Impact preview functional
- [ ] Notifications appear correctly
- [ ] Performance maintained (60fps)
- [ ] Reduced motion support
- [ ] Accessible animations

### 9.4 Testing Requirements

**UX Flow Tests:**
1. Analyze content → View intelligence
2. Apply improvement → See score update
3. Reach milestone → See celebration
4. Apply multiple improvements → See cumulative effect
5. Keyboard navigation → All features accessible

**Performance Tests:**
- Score update < 200ms
- Animation smooth (60fps)
- No memory leaks
- No layout shifts

### 9.5 Files to Create/Modify

**Modified Files:**
- `assets/js/intelligence-panel.js` (add live update features)
- `assets/js/content-improver.js` (emit events)
- `assets/css/intelligence-panel.css` (add animation styles)

---


## TASK 10: Intelligence Cache Layer

**Priority:** HIGH  
**Complexity:** MEDIUM  
**Estimated Time:** 2 days  
**Dependencies:** Tasks 1-7  
**Requirement:** REQ-2.10

### 10.1 Objective

Implement efficient caching system for content intelligence to improve performance and reduce API costs.

### 10.2 Implementation Steps

**Step 1: Create Intelligence Cache Class**
```php
File: includes/class-dodo-intelligence-cache.php

Class: DODO_Intelligence_Cache
Methods:
- get_cached_intelligence($post_id, $content_hash, $keyword)
- set_cached_intelligence($cache_key, $intelligence, $duration)
- invalidate_cache($post_id)
- generate_cache_key($post_id, $content_hash, $keyword)
- cleanup_expired_cache()
```

**Step 2: Implement Multi-Level Caching**
```php
Cache Levels:
1. Object cache (if available) - fastest
2. Transient cache - fast
3. Database query cache - medium
4. Fresh calculation - slowest
```

**Step 3: Implement Cache Keys**
```php
// Main intelligence cache
$key = "dodo_intelligence_{$post_id}_{$content_hash}_{$keyword_hash}";
$duration = 3600; // 1 hour

// Score-only cache (faster retrieval)
$key = "dodo_score_{$post_id}_{$content_hash}";
$duration = 7200; // 2 hours

// Recommendations cache
$key = "dodo_recommendations_{$post_id}_{$content_hash}_{$score}";
$duration = 3600; // 1 hour

// Semantic analysis cache
$key = "dodo_semantic_{$post_id}_{$content_hash}";
$duration = 1800; // 30 minutes
```

**Step 4: Implement Cache Invalidation**
```php
Invalidation Triggers:
- Content change (content_hash mismatch)
- Focus keyword change
- Manual refresh button
- Improvement applied
- 1 hour expiration (automatic)
```

**Step 5: Implement Cache Warming**
```php
// Pre-calculate for recently edited posts
// Background job for popular posts
// Predictive caching based on user behavior
```

**Step 6: Add Cache Monitoring**
```php
Track:
- Cache hit rate
- Cache miss rate
- Cache size
- Cache performance
- API call reduction
```

**Step 7: Implement Cache Cleanup**
```php
// Cleanup old cache entries
// Run daily via WP Cron
// Keep last 7 days only
// Limit total cache size
```

### 10.3 Acceptance Criteria

- [ ] Intelligence cached efficiently
- [ ] Cache hit rate > 70%
- [ ] Cache invalidation works correctly
- [ ] No stale data shown
- [ ] Cache performance < 50ms
- [ ] Cache size monitored
- [ ] Cache cleanup automated
- [ ] Multi-level caching working
- [ ] Unit tests complete
- [ ] Documentation complete

### 10.4 Testing Requirements

**Unit Tests:**
```php
test_get_cached_intelligence_hit()
test_get_cached_intelligence_miss()
test_set_cached_intelligence()
test_invalidate_cache()
test_generate_cache_key()
test_cleanup_expired_cache()
test_cache_hit_rate()
```

**Performance Tests:**
- Cache hit < 50ms
- Cache miss fallback < 3s
- Cache size < 10MB per post
- Cleanup performance < 1s

### 10.5 Files to Create/Modify

**New Files:**
- `includes/class-dodo-intelligence-cache.php` (300+ lines)

**Modified Files:**
- `includes/class-dodo-core.php` (add dependency)
- `includes/class-dodo-admin.php` (use cache in AJAX handlers)

---

## TASK 11: Score History Tracking

**Priority:** MEDIUM  
**Complexity:** LOW  
**Estimated Time:** 2 days  
**Dependencies:** Task 1, Task 10  
**Requirement:** REQ-2.11

### 11.1 Objective

Track content health score over time to show improvement trends and history.

### 11.2 Implementation Steps

**Step 1: Create Database Table**
```sql
File: includes/class-dodo-intelligence-history.php

CREATE TABLE wp_dodo_intelligence_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id BIGINT UNSIGNED NOT NULL,
  health_score TINYINT UNSIGNED NOT NULL,
  seo_score TINYINT UNSIGNED NOT NULL,
  quality_score TINYINT UNSIGNED NOT NULL,
  readability_score TINYINT UNSIGNED NOT NULL,
  semantic_score TINYINT UNSIGNED NOT NULL,
  ai_risk_score TINYINT UNSIGNED NOT NULL,
  confidence_score TINYINT UNSIGNED NOT NULL,
  risk_level VARCHAR(20) NOT NULL,
  recommendations_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  improvements_applied TINYINT UNSIGNED NOT NULL DEFAULT 0,
  content_hash VARCHAR(32) NOT NULL,
  focus_keyword VARCHAR(255),
  word_count INT UNSIGNED NOT NULL DEFAULT 0,
  dimension_breakdown TEXT,
  recommendations TEXT,
  risk_factors TEXT,
  style_profile TEXT,
  analyzed_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_post_id (post_id),
  INDEX idx_analyzed_at (analyzed_at),
  INDEX idx_content_hash (content_hash),
  INDEX idx_health_score (health_score),
  INDEX idx_post_analyzed (post_id, analyzed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Step 2: Create Intelligence History Class**
```php
File: includes/class-dodo-intelligence-history.php

Class: DODO_Intelligence_History
Methods:
- create_table()
- save_snapshot($post_id, $intelligence)
- get_history($post_id, $limit, $date_from, $date_to)
- get_comparison($post_id, $snapshot_ids)
- get_trend($post_id, $days)
- cleanup_old_snapshots($days)
```

**Step 3: Implement Snapshot Saving**
```php
// Save after each analysis
// Include all intelligence data
// Store as JSON in TEXT fields
// Automatic on analyze_content
```

**Step 4: Implement History Retrieval**
```php
// Get last N snapshots
// Filter by date range
// Order by analyzed_at DESC
// Include trend calculation
```

**Step 5: Implement Comparison View**
```php
// Compare two snapshots
// Show score differences
// Show recommendation changes
// Highlight improvements
```

**Step 6: Implement Trend Calculation**
```php
Trend:
- Direction: up/down/stable
- Change: +15 points
- Period: 6 days
- Average improvement per day
```

**Step 7: Implement Cleanup**
```php
// WP Cron daily job
// Keep last 30 days
// Delete older snapshots
// Optimize table
```

### 11.3 Acceptance Criteria

- [ ] Database table created
- [ ] Snapshots saved automatically
- [ ] History retrieved correctly
- [ ] Comparison view functional
- [ ] Trend calculation accurate
- [ ] Cleanup automated
- [ ] Queries optimized (< 100ms)
- [ ] Unit tests complete
- [ ] Documentation complete

### 11.4 Testing Requirements

**Unit Tests:**
```php
test_create_table()
test_save_snapshot()
test_get_history()
test_get_comparison()
test_get_trend()
test_cleanup_old_snapshots()
```

**Integration Tests:**
- Test with multiple snapshots
- Test date range filtering
- Test comparison accuracy
- Test cleanup performance

### 11.5 Files to Create/Modify

**New Files:**
- `includes/class-dodo-intelligence-history.php` (400+ lines)

**Modified Files:**
- `includes/class-dodo-core.php` (add dependency, run create_table)
- `includes/class-dodo-admin.php` (save snapshot after analysis)

---


## TASK 12: Editorial Decision Explainer

**Priority:** HIGH  
**Complexity:** MEDIUM  
**Estimated Time:** 2 days  
**Dependencies:** Tasks 1-7  
**Requirement:** REQ-2.12

### 12.1 Objective

Explain WHY the system makes specific recommendations and HOW it calculates scores.

### 12.2 Implementation Steps

**Step 1: Create Explainer Class**
```php
File: includes/class-dodo-decision-explainer.php

Class: DODO_Decision_Explainer
Methods:
- explain_score($score_type, $score, $factors)
- explain_recommendation($recommendation, $intelligence)
- explain_confidence($confidence, $factors)
- explain_risk($risk_level, $factors)
- format_explanation($explanation)
```

**Step 2: Implement Score Explanations**
```php
Explanation Format:
{
  "what": "Your SEO score is 85/100",
  "why": "You have strong keyword optimization and meta tags, but missing some advanced SEO elements",
  "how": [
    "Focus keyword in title: +15 points",
    "Meta description optimized: +10 points",
    "Keyword in first paragraph: +15 points",
    "Missing: Keyword in H2 headings: -5 points"
  ],
  "improve": "Add your focus keyword to at least one H2 heading to reach 90+",
  "impact": "+5 points potential"
}
```

**Step 3: Implement Recommendation Explanations**
```php
Explanation Format:
{
  "what": "Add focus keyword to title",
  "why": "Search engines heavily weight title tags. Including your focus keyword helps them understand your content's main topic",
  "how": [
    "1. Edit your post title",
    "2. Include 'content marketing' naturally",
    "3. Keep title under 60 characters",
    "4. Make it compelling for users"
  ],
  "impact": "+8 SEO score, better search rankings",
  "examples": {
    "good": "Content Marketing Strategy: Complete Guide for 2026",
    "bad": "Complete Guide for 2026"
  },
  "learn_more": "https://docs.dodo-ai-seo.com/seo-title-optimization"
}
```

**Step 4: Implement Confidence Explanations**
```php
Explanation Format:
{
  "what": "AI confidence is 87% (High)",
  "why": "Your content has clear structure, well-defined sections, and unambiguous improvement opportunities",
  "factors": [
    "Clear topic identification: ✓",
    "Well-structured sections: ✓",
    "Sufficient content length: ✓",
    "Complete metadata: ✓"
  ],
  "increase": "Add more specific examples and data to increase confidence to 95%+"
}
```

**Step 5: Implement Risk Explanations**
```php
Explanation Format:
{
  "what": "Risk level: Medium",
  "why": "Some AI-generated patterns detected, but overall content appears natural",
  "risks": [
    "Repetitive transition phrases (5 instances)",
    "Generic AI conclusion detected",
    "Overly perfect paragraph balance"
  ],
  "mitigate": [
    "Vary your transition words",
    "Add personal insights to conclusion",
    "Mix paragraph lengths naturally"
  ],
  "impact": "Reducing these patterns will lower AI detection risk by 20 points"
}
```

**Step 6: Add Explanation UI**
```javascript
// Tooltip on hover
// Modal on click "Learn More"
// Inline help text
// Contextual explanations
```

**Step 7: Localization Support**
```php
// Turkish translations
// English translations
// Explanation templates
// Dynamic content insertion
```

### 12.3 Acceptance Criteria

- [ ] Every score has explanation
- [ ] Every recommendation has explanation
- [ ] Confidence explained clearly
- [ ] Risk factors explained
- [ ] Explanations accessible via UI
- [ ] Examples provided where helpful
- [ ] Learn more links functional
- [ ] Turkish localization complete
- [ ] Clear, non-technical language
- [ ] Actionable guidance
- [ ] Unit tests complete
- [ ] Documentation complete

### 12.4 Testing Requirements

**Unit Tests:**
```php
test_explain_score()
test_explain_recommendation()
test_explain_confidence()
test_explain_risk()
test_format_explanation()
test_localization()
```

**UX Tests:**
- Test explanation clarity
- Test actionability
- Test example relevance
- Test learn more links
- Test localization

### 12.5 Files to Create/Modify

**New Files:**
- `includes/class-dodo-decision-explainer.php` (400+ lines)

**Modified Files:**
- `includes/class-dodo-core.php` (add dependency)
- `assets/js/intelligence-panel.js` (add explanation UI)
- `assets/css/intelligence-panel.css` (add explanation styles)

---

## IMPLEMENTATION TIMELINE

### Week 1: Foundation & Core Intelligence

**Days 1-2: Task 1 (Health Score Engine)**
- Implement all 5 scoring dimensions
- Test and calibrate scores
- Documentation

**Days 3-4: Task 2 (Confidence Engine)**
- Implement confidence calculation
- Add risk assessment
- Integration with Task 1

**Day 5: Task 10 (Cache Layer)**
- Implement caching system
- Test cache performance
- Integration with Tasks 1-2

### Week 2: Advanced Intelligence & Recommendations

**Days 1-2: Task 3 (Recommendation Engine)**
- Generate recommendations
- Implement prioritization
- Test recommendation quality

**Days 3-4: Task 4 (Semantic Analyzer)**
- OpenAI integration
- Semantic analysis
- LSI keyword identification

**Day 5: Tasks 5-6 (Style & Depth)**
- Style detection
- Depth analysis
- Integration testing

### Week 3: UI/UX & Polish

**Days 1-2: Task 7 (AI Risk Detector)**
- Pattern detection
- Risk scoring
- Humanization suggestions

**Days 3-4: Task 8 (Intelligence Panel UI)**
- Build all UI components
- Implement animations
- Responsive design

**Day 5: Task 9 (Live Experience)**
- Real-time updates
- Milestone celebrations
- Impact previews

### Week 4: Infrastructure & Documentation

**Days 1-2: Task 11 (History Tracking)**
- Database table
- Snapshot saving
- Trend calculation

**Days 3-4: Task 12 (Explainer)**
- Explanation system
- Localization
- UI integration

**Day 5: Final Testing & Documentation**
- Integration testing
- Performance testing
- Documentation review
- Deployment preparation

---

## TESTING STRATEGY

### Unit Testing

**Coverage Target:** 70%+

**Test Files:**
```
tests/test-score-calculator.php
tests/test-confidence-analyzer.php
tests/test-recommendation-engine.php
tests/test-semantic-analyzer.php
tests/test-style-detector.php
tests/test-depth-analyzer.php
tests/test-ai-risk-detector.php
tests/test-intelligence-cache.php
tests/test-intelligence-history.php
tests/test-decision-explainer.php
```

### Integration Testing

**Test Scenarios:**
1. Full analysis workflow
2. Score calculation accuracy
3. Recommendation generation
4. Cache hit/miss scenarios
5. History tracking
6. Real-time updates
7. Error handling

### Performance Testing

**Metrics:**
- Analysis completion < 3s
- Score calculation < 500ms
- Cache hit < 50ms
- UI render < 100ms
- Animation 60fps
- No memory leaks

### User Acceptance Testing

**Test Cases:**
1. Analyze content → View intelligence
2. Apply recommendation → See score update
3. View history → Compare snapshots
4. Understand explanations
5. Navigate with keyboard
6. Use on mobile device

---

## DEPLOYMENT CHECKLIST

### Pre-Deployment

- [ ] All 12 tasks complete
- [ ] Unit tests passing (70%+ coverage)
- [ ] Integration tests passing
- [ ] Performance tests passing
- [ ] Security audit complete
- [ ] Code review complete
- [ ] Documentation complete
- [ ] Changelog updated

### Database

- [ ] Intelligence history table created
- [ ] Indexes added
- [ ] Migration tested
- [ ] Rollback plan ready

### Assets

- [ ] CSS minified
- [ ] JavaScript minified
- [ ] Assets versioned
- [ ] Cache busting implemented

### Configuration

- [ ] Feature flag ready
- [ ] Settings documented
- [ ] API limits configured
- [ ] Cache durations set

### Monitoring

- [ ] Error logging enabled
- [ ] Performance monitoring ready
- [ ] Analytics tracking ready
- [ ] Alert system configured

### Documentation

- [ ] Technical documentation complete
- [ ] User documentation complete
- [ ] API documentation complete
- [ ] Changelog updated
- [ ] Migration guide written

### Deployment

- [ ] Backup database
- [ ] Deploy to staging
- [ ] Test on staging
- [ ] Deploy to production
- [ ] Verify production
- [ ] Monitor for issues

### Post-Deployment

- [ ] Monitor error logs
- [ ] Monitor performance
- [ ] Monitor user feedback
- [ ] Track success metrics
- [ ] Plan iteration

---

## SUCCESS METRICS

### Adoption Metrics

**Week 1:**
- 50% of users view intelligence panel
- 30% of users follow recommendations

**Week 2:**
- 70% of users view intelligence panel
- 50% of users follow recommendations

**Week 4:**
- 80% of users view intelligence panel
- 60% of users follow recommendations

### Quality Metrics

**Week 2:**
- Average score increase: +10 points
- 50% of content reaches "Good" (75+)

**Week 4:**
- Average score increase: +15 points
- 70% of content reaches "Good" (75+)

### Performance Metrics

**Continuous:**
- Analysis < 3s (95th percentile)
- Cache hit rate > 70%
- UI responsiveness < 200ms
- API cost increase < 20%

### User Satisfaction

**Week 4:**
- 4.5/5 satisfaction rating
- 70% find intelligence helpful
- 80% trust recommendations

---

## RISK MITIGATION

### Technical Risks

**Risk:** Performance degradation
**Mitigation:** Aggressive caching, async processing, monitoring

**Risk:** API cost explosion
**Mitigation:** Cache-first, GPT-3.5 for scoring, usage limits

**Risk:** Scoring inaccuracy
**Mitigation:** Calibration, user feedback, continuous refinement

### UX Risks

**Risk:** Information overload
**Mitigation:** Progressive disclosure, top 3-5 recommendations, collapsible

**Risk:** Score obsession
**Mitigation:** Emphasize recommendations, explain limitations

### Integration Risks

**Risk:** Breaking Sprint 1B
**Mitigation:** Regression testing, feature flags, rollback plan

**Risk:** Database performance
**Mitigation:** Indexed queries, cleanup automation, monitoring

---

## NEXT STEPS

**Tasks Document Status:** ✅ COMPLETE

**Ready for Implementation:** ✅ YES

**Implementation Start Date:** TBD  
**Estimated Completion:** 3-4 weeks  
**Target Release:** Version 1.2.0

**Next Actions:**
1. Review and approve tasks document
2. Set up development environment
3. Create feature branch
4. Begin Task 1 implementation
5. Daily progress tracking
6. Weekly sprint reviews

---

**Document Version:** 1.0.0  
**Last Updated:** 2026-05-21  
**Status:** COMPLETE - Ready for Implementation  
**Total Tasks:** 12  
**Estimated Duration:** 3-4 weeks

