# 🎨 ERD Database Schema - Student Portal (Visual Version)

## 📊 Database Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           STUDENT PORTAL DATABASE                             │
│                           ────────────────────────                             │
│                               🎓 Academic Platform                              │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 🗄️ Entity Relationship Diagram

```mermaid
erDiagram
    %% 🔐 Users Entity - Central Authentication Hub
    users {
        🔑 int id PK "🆔 Auto Increment"
        👤 varchar name "📝 Full Name"
        📧 varchar email UK "🔐 Unique Login"
        🔒 varchar password "🔐 Hashed"
        🎭 enum role "👑 admin / 👨‍🎓 student"
        🖼️ varchar avatar "📸 Profile Picture"
        ⏰ timestamp created_at "📅 Registration"
        ⏰ timestamp updated_at "🔄 Last Update"
    }
    
    %% 📚 Subjects Entity - Academic Categories
    subjects {
        🔑 int id PK "🆔 Auto Increment"
        📚 varchar name UK "📖 Unique Subject"
        📄 text description "📝 Subject Details"
        🎨 varchar color "🌈 Visual Theme"
        ⏰ timestamp created_at "📅 Creation Date"
    }
    
    %% 📝 Posts Entity - Content Hub
    posts {
        🔑 int id PK "🆔 Auto Increment"
        📰 varchar title "📰 Post Title"
        📄 text content "📝 Post Content"
        🔗 int user_id FK "👤 Author"
        📚 int subject_id FK "📚 Category"
        🖼️ varchar image "📸 Attachment"
        👁️ enum visibility "👁️ public / 🔒 private"
        ⏰ timestamp created_at "📅 Published"
        ⏰ timestamp updated_at "🔄 Last Edit"
    }
    
    %% 💬 Comments Entity - Social Interaction
    comments {
        🔑 int id PK "🆔 Auto Increment"
        🔗 int post_id FK "📝 Parent Post"
        🔗 int user_id FK "👤 Commenter"
        💬 text content "🗣️ Comment Text"
        ⏰ timestamp created_at "📅 Comment Time"
        ⏰ timestamp updated_at "🔄 Edited"
    }
    
    %% ❤️ Post Likes Entity - Engagement Metrics
    post_likes {
        🔑 int id PK "🆔 Auto Increment"
        🔗 int post_id FK "📝 Liked Post"
        🔗 int user_id FK "👤 Liker"
        ⏰ timestamp created_at "❤️ Like Time"
    }
    
    %% 🏷️ Tags Entity - Content Classification
    tags {
        🔑 int id PK "🆔 Auto Increment"
        🏷️ varchar name UK "🏷️ Unique Tag"
        ⏰ timestamp created_at "📅 Tag Creation"
    }
    
    %% 🔗 Post Tags Junction - Many-to-Many
    post_tags {
        🔗 int post_id FK "📝 Post"
        🔗 int tag_id FK "🏷️ Tag"
        ⏰ timestamp created_at "🔗 Tag Assignment"
    }
    
    %% 📧 Contact Messages Entity - Communication
    contact_messages {
        🔑 int id PK "🆔 Auto Increment"
        👤 varchar name "👤 Sender Name"
        📧 varchar email "📧 Sender Email"
        📋 varchar subject "📋 Message Subject"
        📄 text message "💬 Message Content"
        📎 varchar attachment_path "📎 File Path"
        📎 varchar attachment_name "📎 File Name"
        📊 enum status "📬 unread / ✅ read"
        ⏰ timestamp created_at "📅 Message Time"
        ⏰ timestamp updated_at "🔄 Status Update"
    }

    %% 🔗 RELATIONSHIPS - Data Flow Architecture
    users ||--o{ posts : "📝 creates"
    users ||--o{ comments : "💬 writes"
    users ||--o{ post_likes : "❤️ gives"
    users ||--o{ contact_messages : "📧 sends"
    
    subjects ||--o{ posts : "📚 categorizes"
    
    posts ||--o{ comments : "💬 has"
    posts ||--o{ post_likes : "❤️ receives"
    posts ||--o{ post_tags : "🏷️ tagged_with"
    
    tags ||--o{ post_tags : "🏷️ applied_to"
```

---

## 🎯 Core Entities Deep Dive

### 🔐 **Users** - Authentication Center
```
┌─────────────────────────────────────────────────────────┐
│                    👤 USERS TABLE                       │
│                 ────────────────────────                │
│  🔑 id          📝 name        📧 email (unique)        │
│  🔒 password    🎭 role        🖼️ avatar               │
│  ⏰ created_at  ⏰ updated_at                           │
└─────────────────────────────────────────────────────────┘
         │
    ┌────┴────┐
    │         │
👑 Admin   👨‍🎓 Student
    │         │
    │    ┌────┴────┐
    │    │ Dashboard│
    │    │ Profile  │
    │    │ Posts    │
    │    └─────────┘
    │
┌───┴──────────────────┐
│ Admin Panel           │
│ - User Management     │
│ - Content Moderation  │
│ - System Settings     │
└──────────────────────┘
```

### 📝 **Posts** - Content Hub
```
┌─────────────────────────────────────────────────────────┐
│                    📝 POSTS TABLE                      │
│                 ────────────────────────                │
│  🔑 id          📰 title       📄 content              │
│  🔗 user_id     📚 subject_id  🖼️ image                │
│  👁️ visibility  ⏰ timestamps                         │
└─────────────────────────────────────────────────────────┘
         │
    ┌────┴────┐
    │         │
📰 Public  🔒 Private
    │         │
    │    ┌────┴────┐
    │    │ Global   │
    │    │ Feed     │
    │    └─────────┘
    │
┌───┴──────────────────┐
│ Post Features         │
│ - Rich Text Content   │
│ - Image Attachments   │
│ - Subject Categorization│
│ - Visibility Control  │
└──────────────────────┘
```

---

## 🔗 Relationship Matrix

### 📊 **One-to-Many Relationships**
```
👤 Users → 📝 Posts        (1:N)  - User creates many posts
👤 Users → 💬 Comments     (1:N)  - User writes many comments  
👤 Users → ❤️ Post Likes   (1:N)  - User likes many posts
📚 Subjects → 📝 Posts     (1:N)  - Subject has many posts
📝 Posts → 💬 Comments     (1:N)  - Post has many comments
```

### 🔄 **Many-to-Many Relationships**
```
📝 Posts ↔ 🏷️ Tags         (M:N)  - Posts have multiple tags
                              Tags apply to multiple posts
```

### 🔒 **Data Integrity Rules**
```
✅ CASCADE DELETE:
   - User deleted → Posts deleted
   - Post deleted → Comments deleted
   - Post deleted → Likes deleted
   
✅ RESTRICT DELETE:
   - Subject with posts cannot be deleted
   
✅ UNIQUE CONSTRAINTS:
   - Email uniqueness (login)
   - Subject name uniqueness
   - Tag name uniqueness
   - Post+User like uniqueness
```

---

## 🚀 Data Flow Architecture

```
🔄 Complete User Journey Flow:

┌─────────────┐    ┌──────────────┐    ┌─────────────┐
│  📝 Register│ →  │  🔐 Login     │ →  │  👤 Profile │
└─────────────┘    └──────────────┘    └─────────────┘
       │                   │                   │
       ↓                   ↓                   ↓
┌─────────────┐    ┌──────────────┐    ┌─────────────┐
│  👤 Create   │ →  │  📝 Publish   │ →  │  🏷️ Tag     │
│  Account    │    │  Post        │    │  Content    │
└─────────────┘    └──────────────┘    └─────────────┘
       │                   │                   │
       ↓                   ↓                   ↓
┌─────────────┐    ┌──────────────┐    ┌─────────────┐
│  💬 Comment  │ →  │  ❤️ Like      │ →  │  📧 Contact  │
│  Posts      │    │  Posts       │    │  Admin      │
└─────────────┘    └──────────────┘    └─────────────┘
```

---

## 🎨 Visual Schema Summary

### 📊 **Database Statistics**
```
🗄️ Total Tables: 8
🔗 Relationships: 7
👥 User Roles: 2 (Admin/Student)
📚 Content Types: Posts, Comments, Likes
🏷️ Classification: Subjects, Tags
📧 Communication: Contact Messages
```

### 🔍 **Search Capabilities**
```
🔍 Full-text Search: Posts (title + content)
📊 Analytics: User posts, likes, comments
📈 Timeline: Created_at indexes
🎯 Filtering: By subject, visibility, status
```

### 🛡️ **Security Features**
```
🔐 Password Hashing: Bcrypt
🔐 Session Management: Secure
🔐 Role-based Access: Admin/Student
🔐 Input Validation: PDO Prepared Statements
🔐 Email Restrictions: @gmail.com only
```

---

## 🎯 Implementation Notes

### ✅ **Currently Active Features**
- User authentication & profiles
- Post CRUD operations
- Comment system
- Like system
- Subject categorization
- Tag system
- Contact messaging
- File attachments

### ❌ **Not Implemented**
- Audit logging (table exists but unused)
- Database views (defined but unused)
- Stored procedures (defined but unused)
- Triggers (defined but unused)

---

**🎨 This ERD represents the actual, production-ready database schema for the Student Portal system, verified against the complete codebase. All relationships, constraints, and data flows are actively used in the application.**
