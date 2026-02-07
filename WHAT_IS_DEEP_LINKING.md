What is Deep Linking?                                                                                                                                       
                                                                                                                                                              
  Deep Linking 2.0 is like a "smart content picker" that lets instructors browse and select specific content from Pressbooks to embed in their Moodle course. 
                                                                                                                                                              
  Without Deep Linking (Old Way) ❌

  - Instructor creates LTI activity in Moodle
  - Must manually copy/paste a Pressbooks URL
  - URL could be wrong or break later
  - Students click → always go to the same generic page
  - No way to select specific chapters or content

  With Deep Linking (New Way) ✅

  - Instructor creates LTI activity in Moodle
  - Clicks "Select Content" button
  - Browses Pressbooks like a library - sees all available books, chapters, pages
  - Selects exactly what they want (e.g., "Chapter 5: Photosynthesis")
  - Moodle stores that specific link
  - Students click → go directly to that chapter

  Real-World Use Cases

  Example 1: Biology Course

  Scenario: Professor teaching Biology 101 wants students to read specific chapters from an OER textbook in Pressbooks.

  With Deep Linking:
  1. Professor adds "Read Chapter 3" activity in Moodle
  2. Clicks activity setup → Pressbooks content picker opens
  3. Browses the Biology textbook
  4. Selects "Chapter 3: Cell Structure"
  5. Saves → Students now click and go directly to Chapter 3
  6. Next week: Creates another activity for Chapter 5
  7. Each activity goes to the exact right content

  Example 2: Literature Course

  Scenario: Professor wants students to read different poems from a Pressbooks poetry collection.

  With Deep Linking:
  - Week 1 Activity: "Poem Analysis 1" → Links to specific poem
  - Week 2 Activity: "Poem Analysis 2" → Links to different poem
  - Week 3 Activity: "Compare Poems" → Links to comparison page

  Each activity has precise, granular content targeting.

  Example 3: Multi-Book Library

  Scenario: University has 50 OER textbooks in Pressbooks. Different courses use different books.

  With Deep Linking:
  - Chemistry prof selects from Chemistry textbook
  - Physics prof selects from Physics textbook
  - Both use same LTI tool, but browse different content
  - No manual URL management needed

  What You're Seeing Now

  When you clicked the Deep Linking Test activity, you saw the Pressbooks homepage because:
  1. ✅ Deep Linking flow is working
  2. ⚠️ We haven't built the content picker UI yet

  Current behavior:
  - Shows Pressbooks homepage (default content)
  - Automatically creates a JWT with that content
  - Should redirect you back to Moodle

  Future behavior (after UI is built):
  - Shows interactive content browser
  - List of all books, chapters, pages
  - Search and filter functionality
  - Preview content before selecting
  - "Select This Content" button
