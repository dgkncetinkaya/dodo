# Sprint 5: GEO & Intelligence Engine - Requirements

**Document Version:** 1.0  
**Created:** 2026-05-22  
**Status:** Draft

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Strategic Context](#2-strategic-context)
3. [Functional Requirements](#3-functional-requirements)
4. [Non-Functional Requirements](#4-non-functional-requirements)
5. [Technical Requirements](#5-technical-requirements)
6. [Integration Requirements](#6-integration-requirements)
7. [Database Requirements](#7-database-requirements)
8. [UI/UX Requirements](#8-uiux-requirements)
9. [Security Requirements](#9-security-requirements)
10. [Performance Requirements](#10-performance-requirements)
11. [Constraints](#11-constraints)
12. [Dependencies](#12-dependencies)
13. [Success Criteria](#13-success-criteria)

---

## 1. Executive Summary

### 1.1 Vision

Transform DODO AI SEO from a traditional "AI blog writer" into an **AI-native SEO + GEO intelligence platform** that optimizes content for AI Overview, ChatGPT, Gemini, Claude, and other LLM retrieval systems.

### 1.2 Strategic Shift

**Current State:** AI content generator focused on traditional SEO  
**Target State:** Intelligence platform for AI-era search and retrieval

### 1.3 Core Value Proposition

- **For Users:** Create content that ranks in AI Overviews and gets cited by LLMs
- **For Business:** Differentiate from classic SEO plugins
- **For Market:** First WordPress plugin optimized for GEO (Generative Engine Optimization)

### 1.4 Key Deliverables

1. GEO / AI Visibility Engine
2. AI Answer Block Engine
3. Content Intelligence Engine
4. Humanization Engine
5. Production Publishing Pipeline
6. Analytics & GEO Reporting

---

## 2. Strategic Context

### 2.1 Market Shift

**Traditional SEO → GEO (Generative Engine Optimization)**

- Google AI Overviews replacing traditional snippets
- ChatGPT, Perplexity, Gemini becoming search alternatives
- LLMs citing sources in responses
- Content needs to be "LLM-friendly" not just "SEO-friendly"

### 2.2 Competitive Landscape

**Current SEO Plugins:**
- Yoast SEO: Traditional keyword optimization
- Rank Math: Classic SEO metrics
- All in One SEO: Standard SEO features

**DODO Differentiation:**
- AI Overview optimization
- LLM retrieval scoring
- Humanization analysis
- GEO intelligence

### 2.3 User Needs

**Content Creators Need:**
- Understand if content will appear in AI Overviews
- Know if LLMs can extract and cite their content
- Ensure content doesn't sound robotic
- Manage content at scale with intelligence

---

## 3. Functional Requirements

### FR-1: GEO / AI Visibility Engine

**Priority:** Critical  
**Class:** `DODO_GEO_Engine`

#### FR-1.1 Content Analysis

**Requirement:** System must analyze content for AI retrievability

**Analysis Dimensions:**

1. **Answerability Score (0-100)**
   - Does content directly answer questions?
   - Are answers concise and extractable?
   - Is answer format LLM-friendly?

2. **Citation Potential (0-100)**
   - Is content authoritative?
   - Are sources and data present?
   - Is attribution clear?

3. **Chunk Quality (0-100)**
   - Are paragraphs well-structured?
   - Is information density optimal?
   - Can LLMs extract clean chunks?

4. **Entity Richness (0-100)**
   - Are named entities present?
   - Is entity context clear?
   - Are relationships defined?

5. **Semantic Clarity (0-100)**
   - Is language unambiguous?
   - Are concepts well-defined?
   - Is terminology consistent?

6. **Retrieval Friendliness (0-100)**
   - Is content scannable?
   - Are key points highlighted?
   - Is structure logical?

7. **Passage Extraction Suitability (0-100)**
   - Can passages stand alone?
   - Is context self-contained?
   - Are passages quotable?

8. **AI Overview Compatibility (0-100)**
   - Does content match AI Overview format?
   - Are summaries present?
   - Is information hierarchical?

9. **Conversational Intent Match (0-100)**
   - Does content answer natural questions?
   - Is tone conversational?
   - Are follow-up questions addressed?

10. **Featured Snippet Suitability (0-100)**
    - Is content snippet-ready?
    - Are definitions clear?
    - Are lists and tables present?

#### FR-1.2 GEO Score Calculation

**Requirement:** System must calculate overall GEO score (0-100)

**Formula:**
```
GEO Score = (
  Answerability * 0.15 +
  Citation Potential * 0.10 +
  Chunk Quality * 0.10 +
  Entity Richness * 0.10 +
  Semantic Clarity * 0.10 +
  Retrieval Friendliness * 0.15 +
  Passage Extraction * 0.10 +
  AI Overview Compatibility * 0.10 +
  Conversational Intent * 0.05 +
  Featured Snippet * 0.05
)
```

**Score Ranges:**
- 90-100: Excellent (AI Overview ready)
- 75-89: Good (High retrieval potential)
- 60-74: Fair (Needs improvement)
- 0-59: Poor (Not LLM-friendly)

#### FR-1.3 Recommendations

**Requirement:** System must provide actionable recommendations

**Output Format:**
```php
array(
    'geo_score' => 78,
    'strengths' => array(
        'High answerability score',
        'Good entity richness',
        'Clear semantic structure'
    ),
    'weaknesses' => array(
        'Low citation potential',
        'Poor chunk quality',
        'Missing conversational elements'
    ),
    'recommendations' => array(
        array(
            'priority' => 'high',
            'category' => 'citation',
            'action' => 'Add authoritative sources and data',
            'impact' => '+8 points'
        ),
        array(
            'priority' => 'medium',
            'category' => 'structure',
            'action' => 'Break long paragraphs into chunks',
            'impact' => '+5 points'
        )
    )
)
```

---

### FR-2: AI Answer Block Engine

**Priority:** High  
**Integration:** Content generation pipeline

#### FR-2.1 Block Types

**Requirement:** System must generate LLM-friendly content blocks

**Block Types:**

1. **Short Answer Block**
   - 2-3 sentence direct answer
   - Placed at content start
   - Standalone and quotable

2. **Featured Snippet Block**
   - Definition or explanation
   - 40-60 words
   - Structured format

3. **Direct Answer Paragraph**
   - Question + Answer format
   - Clear and concise
   - No fluff

4. **Comparison Section**
   - Side-by-side comparison
   - Table or structured list
   - Clear differentiators

5. **AI Summary Section**
   - Key points summary
   - Bullet list format
   - Scannable

6. **FAQ Intent Cluster**
   - Related questions
   - Brief answers
   - Schema markup ready

#### FR-2.2 Auto-Generation

**Requirement:** System must auto-generate blocks during content creation

**Trigger Points:**
- New blog generation
- Content improvement
- Manual request

**Configuration:**
- Enable/disable per block type
- Placement preferences
- Length preferences

---

### FR-3: Content Intelligence Engine

**Priority:** Critical  
**Class:** `DODO_Content_Intelligence`

#### FR-3.1 Topic Map Builder

**Requirement:** System must map site-wide topic structure

**Features:**

1. **Pillar Topic Detection**
   - Identify main topics
   - Calculate topic authority
   - Map topic hierarchy

2. **Support Topic Mapping**
   - Find supporting content
   - Identify topic clusters
   - Calculate cluster strength

3. **Semantic Cluster Analysis**
   - Group related content
   - Identify cluster gaps
   - Suggest cluster expansion

4. **Missing Topic Detection**
   - Identify content gaps
   - Suggest new topics
   - Prioritize by opportunity

**Output:**
```php
array(
    'pillar_topics' => array(
        array(
            'topic' => 'WordPress SEO',
            'authority_score' => 85,
            'post_count' => 12,
            'cluster_strength' => 'strong'
        )
    ),
    'support_topics' => array(...),
    'missing_topics' => array(...),
    'cluster_gaps' => array(...)
)
```

#### FR-3.2 Cannibalization Detector

**Requirement:** System must detect content cannibalization

**Detection Methods:**

1. **Overlap Detection**
   - Keyword overlap analysis
   - Content similarity scoring
   - Intent overlap detection

2. **Duplicate Intent**
   - Same search intent
   - Competing for same queries
   - Weak differentiation

3. **Weak Differentiation**
   - Similar content structure
   - Minimal unique value
   - Consolidation opportunity

**Output:**
```php
array(
    'cannibalization_issues' => array(
        array(
            'post_1' => 123,
            'post_2' => 456,
            'overlap_score' => 78,
            'issue_type' => 'duplicate_intent',
            'recommendation' => 'Consolidate or differentiate'
        )
    )
)
```

#### FR-3.3 Internal Link Intelligence

**Requirement:** System must provide intelligent link suggestions

**Features:**

1. **Semantic Link Suggestions**
   - Context-aware suggestions
   - Relevance scoring
   - Anchor text suggestions

2. **Anchor Suggestions**
   - Natural anchor text
   - Keyword-rich but natural
   - Varied anchor patterns

3. **Orphan Detection**
   - Find unlinked content
   - Suggest link opportunities
   - Prioritize by importance

#### FR-3.4 Topical Authority Tracking

**Requirement:** System must track topical authority

**Metrics:**

1. **Cluster Completeness**
   - Content coverage
   - Topic depth
   - Missing subtopics

2. **Authority Score**
   - Content quality
   - Internal linking
   - External signals

3. **Semantic Coverage**
   - Entity coverage
   - Concept coverage
   - Relationship mapping

---

### FR-4: Humanization Engine

**Priority:** High  
**Class:** `DODO_Humanizer`

#### FR-4.1 Robotic Pattern Detection

**Requirement:** System must detect AI-generated patterns

**Detection Patterns:**

1. **Robotic Phrasing**
   - Overused AI phrases
   - Unnatural transitions
   - Repetitive structures

2. **Repetitive Sentence Structure**
   - Same sentence length
   - Same sentence patterns
   - Monotonous rhythm

3. **Burstiness Analysis**
   - Sentence length variation
   - Paragraph length variation
   - Rhythm analysis

4. **Transition Quality**
   - Natural transitions
   - Logical flow
   - Conversational bridges

5. **Conversational Balance**
   - Formal vs casual
   - Active vs passive
   - Direct vs indirect

6. **Paragraph Rhythm**
   - Varied paragraph length
   - Natural pacing
   - Reader engagement

#### FR-4.2 Humanization Presets

**Requirement:** System must offer humanization presets

**Presets:**

1. **Human-like**
   - Natural conversational tone
   - Varied sentence structure
   - Personal touches

2. **Editorial**
   - Professional journalism style
   - Clear and authoritative
   - Well-structured

3. **Technical**
   - Precise and accurate
   - Technical terminology
   - Logical structure

4. **Founder Voice**
   - Personal and authentic
   - Story-driven
   - Opinionated

5. **Conversion Focused**
   - Persuasive
   - Action-oriented
   - Benefit-driven

#### FR-4.3 Controlled Humanization

**Requirement:** System must apply controlled humanization

**Approach:**
- NOT complete rewrite
- Targeted improvements
- Preserve core message
- Maintain SEO keywords

**Process:**
1. Identify robotic sections
2. Suggest improvements
3. Apply selectively
4. Preserve intent

---

### FR-5: Production Publishing Pipeline

**Priority:** High  
**Class:** `DODO_Publishing_Pipeline`

#### FR-5.1 Scheduled Publishing

**Requirement:** System must support advanced scheduling

**Features:**

1. **Queue Scheduling**
   - Add to publish queue
   - Set publish date/time
   - Priority ordering

2. **Category Scheduling**
   - Schedule by category
   - Distribute across categories
   - Balance content mix

3. **Publish Windows**
   - Define publish windows
   - Avoid weekends (optional)
   - Time interval between posts

#### FR-5.2 Smart Review Queue

**Requirement:** System must support review workflow

**Workflow States:**

1. **Review Required**
   - Manual review needed
   - Approval required
   - Edit before publish

2. **Auto Publish**
   - Automatic publishing
   - No review needed
   - Scheduled execution

3. **Approval Flow**
   - Submit for approval
   - Approve/reject
   - Revision requests

#### FR-5.3 Content Memory

**Requirement:** System must remember content preferences

**Memory Items:**

1. **Brand Tone**
   - Preferred tone
   - Voice characteristics
   - Style guidelines

2. **CTA Style**
   - CTA patterns
   - CTA placement
   - CTA language

3. **Forbidden Words**
   - Words to avoid
   - Phrases to avoid
   - Replacement suggestions

4. **Preferred Entities**
   - Preferred terminology
   - Brand names
   - Product names

5. **Internal Link Behavior**
   - Link density preferences
   - Anchor text patterns
   - Link placement rules

#### FR-5.4 Multi-Site Readiness

**Requirement:** System must support multi-site architecture

**Features:**

1. **Multiple Domains**
   - Domain-specific settings
   - Domain-specific memory
   - Cross-domain analytics

2. **Multiple Brands**
   - Brand-specific tone
   - Brand-specific style
   - Brand-specific rules

3. **Centralized Content Memory**
   - Shared learnings
   - Shared patterns
   - Shared optimizations

---

### FR-6: Analytics & GEO Reporting

**Priority:** Medium  
**Integration:** Analytics Dashboard

#### FR-6.1 New Metrics

**Requirement:** System must track GEO and intelligence metrics

**Metrics:**

1. **GEO Score** - Overall GEO performance
2. **AI Visibility Score** - LLM retrievability
3. **Retrieval Score** - Extraction quality
4. **Authority Growth** - Topical authority trend
5. **Entity Coverage** - Entity richness
6. **Internal Link Health** - Link structure quality
7. **Answerability Score** - Question answering
8. **Humanization Score** - Content naturalness

#### FR-6.2 Trend System

**Requirement:** System must track historical trends

**Features:**

1. **Historical Scores**
   - Daily snapshots
   - Weekly aggregates
   - Monthly trends

2. **Growth Graphs**
   - Score progression
   - Improvement tracking
   - Goal tracking

3. **Improvement Tracking**
   - Before/after comparison
   - Impact measurement
   - ROI calculation

---

## 4. Non-Functional Requirements

### NFR-1: Performance

**Requirement:** System must maintain performance standards

**Targets:**
- GEO analysis: < 5 seconds
- Humanization analysis: < 3 seconds
- Topic mapping: < 10 seconds
- Cache hit rate: > 70%
- No timeout errors

### NFR-2: Scalability

**Requirement:** System must scale with content volume

**Targets:**
- Support 10,000+ posts
- Handle 100+ concurrent analyses
- Process 1,000+ queue jobs/day

### NFR-3: Reliability

**Requirement:** System must be production-reliable

**Targets:**
- 99.9% uptime
- Graceful API failure handling
- Automatic retry on transient errors
- No data loss

### NFR-4: Maintainability

**Requirement:** System must be maintainable

**Standards:**
- Class-based architecture
- Dependency injection
- Comprehensive logging
- Clear error messages

### NFR-5: Compatibility

**Requirement:** System must maintain compatibility

**Requirements:**
- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+
- No breaking changes to existing features

---

## 5. Technical Requirements

### TR-1: Architecture

**Requirement:** Follow established architecture patterns

**Patterns:**
- Singleton for core classes
- Dependency injection
- Factory pattern for engines
- Observer pattern for events

### TR-2: Database

**Requirement:** Use migration-safe database changes

**Standards:**
- Version-controlled migrations
- Rollback support
- Data integrity checks
- Index optimization

### TR-3: API Integration

**Requirement:** Integrate with OpenAI API efficiently

**Standards:**
- Token optimization
- Cost tracking
- Rate limit handling
- Fallback strategies

### TR-4: Caching

**Requirement:** Implement intelligent caching

**Strategy:**
- Cache GEO scores (24 hours)
- Cache topic maps (7 days)
- Cache humanization analysis (24 hours)
- Invalidate on content change

---

## 6. Integration Requirements

### IR-1: Existing Systems

**Requirement:** Integrate with existing DODO systems

**Integration Points:**

1. **Content Generator**
   - Add AI Answer Blocks
   - Apply humanization
   - Use content memory

2. **Content Improver**
   - Add GEO analysis
   - Add humanization analysis
   - Show recommendations

3. **Analytics Dashboard**
   - Add GEO metrics
   - Add trend graphs
   - Add intelligence insights

4. **Job Queue**
   - Queue GEO analysis
   - Queue topic mapping
   - Queue humanization

5. **Token Optimizer**
   - Track GEO analysis costs
   - Optimize humanization costs
   - Budget management

6. **Error Handler**
   - Handle GEO errors
   - Handle API failures
   - Graceful degradation

---

## 7. Database Requirements

### DR-1: New Tables

**Requirement:** Create new database tables

**Tables:**

1. **dodo_geo_scores**
```sql
CREATE TABLE dodo_geo_scores (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    geo_score int(11) NOT NULL,
    answerability_score int(11) NOT NULL,
    citation_potential int(11) NOT NULL,
    chunk_quality int(11) NOT NULL,
    entity_richness int(11) NOT NULL,
    semantic_clarity int(11) NOT NULL,
    retrieval_friendliness int(11) NOT NULL,
    passage_extraction int(11) NOT NULL,
    ai_overview_compatibility int(11) NOT NULL,
    conversational_intent int(11) NOT NULL,
    featured_snippet int(11) NOT NULL,
    analysis_data longtext,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY geo_score (geo_score),
    KEY updated_at (updated_at)
);
```

2. **dodo_content_intelligence**
```sql
CREATE TABLE dodo_content_intelligence (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    analysis_type varchar(50) NOT NULL,
    analysis_data longtext NOT NULL,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY analysis_type (analysis_type),
    KEY updated_at (updated_at)
);
```

3. **dodo_humanization_analysis**
```sql
CREATE TABLE dodo_humanization_analysis (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    humanization_score int(11) NOT NULL,
    robotic_score int(11) NOT NULL,
    burstiness_score int(11) NOT NULL,
    transition_score int(11) NOT NULL,
    conversational_score int(11) NOT NULL,
    rhythm_score int(11) NOT NULL,
    analysis_data longtext,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY humanization_score (humanization_score),
    KEY updated_at (updated_at)
);
```

4. **dodo_publishing_pipeline**
```sql
CREATE TABLE dodo_publishing_pipeline (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    status varchar(20) NOT NULL,
    scheduled_at datetime NOT NULL,
    published_at datetime DEFAULT NULL,
    review_status varchar(20) DEFAULT NULL,
    reviewer_id bigint(20) DEFAULT NULL,
    priority int(11) NOT NULL DEFAULT 10,
    metadata longtext,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY status (status),
    KEY scheduled_at (scheduled_at),
    KEY review_status (review_status)
);
```

---

## 8. UI/UX Requirements

### UR-1: Design System

**Requirement:** Use existing design system

**Standards:**
- Use global CSS variables
- Follow existing patterns
- Maintain light theme
- No redesign

### UR-2: New UI Components

**Requirement:** Add minimal new UI components

**Components:**

1. **GEO Score Card**
   - Display GEO score
   - Show score breakdown
   - List recommendations

2. **Humanization Panel**
   - Display humanization score
   - Show detected issues
   - Suggest improvements

3. **Topic Map Visualization**
   - Show topic clusters
   - Display relationships
   - Highlight gaps

4. **Publishing Pipeline Dashboard**
   - Show scheduled posts
   - Display review queue
   - Track publish status

### UR-3: Integration Points

**Requirement:** Integrate UI into existing pages

**Pages:**

1. **Content Improver**
   - Add GEO analysis tab
   - Add humanization tab
   - Show recommendations

2. **Analytics Dashboard**
   - Add GEO metrics
   - Add trend graphs
   - Add intelligence insights

3. **New Blog Page**
   - Add AI Answer Block options
   - Add humanization preset selector
   - Add publishing pipeline options

---

## 9. Security Requirements

### SR-1: Data Security

**Requirement:** Protect sensitive data

**Measures:**
- Sanitize all inputs
- Escape all outputs
- Validate nonces
- Check capabilities

### SR-2: API Security

**Requirement:** Secure API communications

**Measures:**
- Mask API keys in logs
- Use HTTPS only
- Validate API responses
- Rate limit protection

### SR-3: Database Security

**Requirement:** Secure database operations

**Measures:**
- Use prepared statements
- Validate data types
- Prevent SQL injection
- Sanitize stored data

---

## 10. Performance Requirements

### PR-1: Analysis Performance

**Requirement:** Optimize analysis performance

**Targets:**
- GEO analysis: < 5s
- Humanization: < 3s
- Topic mapping: < 10s
- Cache hit rate: > 70%

### PR-2: Database Performance

**Requirement:** Optimize database queries

**Measures:**
- Use indexes
- Limit result sets
- Cache query results
- Optimize joins

### PR-3: API Performance

**Requirement:** Optimize API usage

**Measures:**
- Batch requests
- Use caching
- Chunk large content
- Implement retry logic

---

## 11. Constraints

### C-1: Technical Constraints

- Must use WordPress native patterns
- Must maintain PHP 7.4+ compatibility
- Must not break existing features
- Must not require heavy JS frameworks

### C-2: Business Constraints

- Must complete in 4-6 weeks
- Must maintain production stability
- Must not increase hosting requirements significantly

### C-3: Design Constraints

- Must use existing design system
- Must not redesign existing UI
- Must maintain light theme
- Must follow established patterns

---

## 12. Dependencies

### D-1: External Dependencies

- OpenAI API (GPT-4 + GPT-3.5-turbo)
- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+

### D-2: Internal Dependencies

- Sprint 1-4 features
- Existing design system
- Job queue system
- Token optimizer
- Error handler
- Logger system

---

## 13. Success Criteria

### SC-1: Functional Success

- ✅ All 6 phases implemented
- ✅ All features working as specified
- ✅ No breaking changes
- ✅ All tests passing

### SC-2: Performance Success

- ✅ Analysis times within targets
- ✅ Cache hit rate > 70%
- ✅ No timeout errors
- ✅ Database queries optimized

### SC-3: Quality Success

- ✅ PHP syntax validation passed
- ✅ Security audit passed
- ✅ Code review passed
- ✅ User acceptance testing passed

### SC-4: Business Success

- ✅ 70%+ average GEO score
- ✅ 60%+ humanization score
- ✅ 90%+ scheduled publish success
- ✅ User satisfaction > 80%

---

**Document Status:** Draft - Ready for Review  
**Next Step:** Create Design Document  
**Last Updated:** 2026-05-22
