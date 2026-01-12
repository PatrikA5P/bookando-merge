export const messages = {
  de: {
    mod: {
      academy: {
        title: 'Academy',
        tabs: {
          courses: 'Kurse',
          training: 'Ausbildungskarten'
        },
        course_tabs: {
          definition: 'Kursdefinition',
          planning: 'Kursplanung'
        },
        quiz_tabs: {
          general: 'Allgemein',
          questions: 'Fragen',
          settings: 'Einstellungen'
        },
        actions: {
          add_course: 'Neuen Kurs erfassen',
          add_card: 'Ausbildungskarte erstellen',
          add_topic: 'Thema hinzufügen',
          add_lesson: 'Lektion hinzufügen',
          add_quiz: 'Test hinzufügen',
          add_question: 'Frage hinzufügen',
          add_option: 'Option hinzufügen',
          add_answer: 'Antwort hinzufügen',
          add_pair: 'Paar hinzufügen',
          add_item: 'Element hinzufügen',
          add_image_url: 'Bild-URL hinzufügen',
          add_video: 'Video hinzufügen',
          add_file: 'Datei hinzufügen',
          edit_topic: 'Thema bearbeiten',
          edit_lesson: 'Lektion bearbeiten',
          edit_quiz: 'Test bearbeiten',
          edit_question: 'Frage bearbeiten',
          save: 'Speichern',
          cancel: 'Abbrechen',
          delete: 'Löschen',
          refresh: 'Aktualisieren',
          progress: 'Fortschritt aktualisieren'
        },
        labels: {
          title: 'Titel',
          description: 'Beschreibung',
          category: 'Kategorie',
          level: 'Schwierigkeitsgrad',
          modules: 'Module',
          module_title: 'Modul',
          module_goal: 'Lernziel',
          duration: 'Dauer (Minuten)',
          student: 'Schüler:in',
          instructor: 'Instruktor:in',
          program: 'Programm',
          notes: 'Notizen',
          progress: 'Fortschritt',
          milestones: 'Meilensteine',
          add_milestone: 'Meilenstein hinzufügen',
          mark_completed: 'Abschließen',
          remove: 'Entfernen',

          // Kursdefinition
          course_type: 'Kursart',
          author: 'Autor',
          max_participants: 'Max. Teilnehmer',
          visibility: 'Sichtbarkeit',
          display_from: 'Anzeigen von',
          display_until: 'Anzeigen bis',
          tags: 'Schlagwörter',
          featured_image: 'Beitragsbild',
          intro_video: 'Intro-Video',
          sequential_topics: 'Themen müssen in vorgeschriebener Reihenfolge absolviert werden',

          // Kursplanung
          topics: 'Themen',
          topic_title: 'Thementitel',
          topic_summary: 'Zusammenfassung',
          lessons: 'Lektionen',
          quizzes: 'Tests',
          lesson_name: 'Lektionsname',
          lesson_content: 'Inhalt',
          images: 'Bilder',
          videos: 'Videos',
          files: 'Übungsdateien',

          // Quiz
          quiz_title: 'Test-Titel',
          quiz_summary: 'Zusammenfassung',
          questions: 'Fragen',
          question: 'Frage',
          question_type: 'Fragetyp',
          question_text: 'Fragestellung',
          time_limit: 'Zeitlimit (Sekunden)',
          points: 'Punkte',
          answer_options: 'Antwortmöglichkeiten',
          correct: 'Richtig',
          correct_answer: 'Richtige Antwort',
          correct_answer_type: 'Art der richtigen Antwort',
          true: 'Wahr',
          false: 'Falsch',
          min: 'Min',
          max: 'Max',
          step: 'Schritte',
          exact_value: 'Exakter Wert',
          correct_value: 'Richtiger Wert',
          range: 'Bereich',
          range_min: 'Bereich Min',
          range_max: 'Bereich Max',
          image_url: 'Bild-URL',
          sample_answer: 'Musterantwort',
          text_with_blanks: 'Text mit Lücken',
          answers: 'Antworten',
          blank: 'Lücke',
          matching_pairs: 'Zuordnungspaare',
          left_items: 'Linke Elemente',
          right_items: 'Rechte Elemente',
          correct_matches: 'Richtige Zuordnungen',
          items_to_sort: 'Zu sortierende Elemente',
          item: 'Element',

          // Quiz-Einstellungen
          attempts_allowed: 'Zulässige Versuche',
          unlimited: 'Unbegrenzt',
          limited: 'Begrenzt',
          pass_percentage: 'Mindestpunktzahl zum Bestehen (%)',
          pass_threshold: 'Mindestpunktzahl',
          questions_to_show: 'Anzahl anzuzeigender Fragen',
          all_questions: 'Alle Fragen',
          random_selection: 'Zufällige Auswahl',
          answer_order: 'Reihenfolge der Antworten',
          randomize_answers: 'Antworten zufällig mischen',
          question_layout: 'Fragenlayout',
          show_feedback: 'Feedback anzeigen'
        },
        sections: {
          basic_info: 'Grundlegende Informationen',
          participation: 'Teilnahme & Sichtbarkeit',
          categorization: 'Kategorisierung',
          media: 'Medien',
          quiz_attempts: 'Versuchseinstellungen',
          quiz_display: 'Anzeigeeinstellungen',
          question_data: 'Fragedetails'
        },
        course_types: {
          online: 'Online',
          physical: 'Präsenz'
        },
        visibility: {
          public: 'Öffentlich',
          logged_in: 'Nur eingeloggte Benutzer',
          private: 'Privat'
        },
        levels: {
          beginner: 'Anfänger',
          intermediate: 'Mittel',
          advanced: 'Fortgeschritten'
        },
        question_types: {
          quiz_single: 'Quiz (Einzelauswahl)',
          quiz_multiple: 'Quiz (Mehrfachauswahl)',
          true_false: 'Richtig/Falsch',
          slider: 'Schieberegler',
          pin_answer: 'Antwort anheften',
          essay: 'Offene Frage/Aufsatz',
          fill_blank: 'Lückentext',
          short_answer: 'Kurzantwort',
          matching: 'Übereinstimmung',
          image_answer: 'Bildbeantwortung',
          sorting: 'Sortierung',
          puzzle: 'Puzzle (Drag & Drop)'
        },
        layouts: {
          single: 'Einzelfragen (mit "Weiter"-Button)',
          all: 'Alle Fragen untereinander (mit "Abschließen")'
        },
        feedback: {
          immediate: 'Sofort anzeigen',
          end: 'Erst am Ende anzeigen'
        },
        placeholders: {
          topic_title: 'Thementitel eingeben...',
          lesson_name: 'Lektionsname eingeben...',
          lesson_content: 'Inhalt der Lektion hier eingeben...',
          quiz_title: 'Test-Titel eingeben...',
          option_text: 'Antwortmöglichkeit eingeben...',
          add_tag: 'Schlagwort hinzufügen...',
          left_item: 'Linkes Element',
          right_item: 'Rechtes Element'
        },
        help: {
          max_participants: 'Leer lassen für unbegrenzte Teilnehmerzahl',
          intro_video: 'YouTube-URL oder Direktlink zu Videodatei',
          time_limit: 'Leer lassen für kein Zeitlimit',
          sample_answer: 'Optional: Beispielantwort zur Orientierung',
          fill_blank: 'Verwenden Sie ___ für Lücken im Text',
          sorting: 'Ziehen Sie die Elemente, um die richtige Reihenfolge festzulegen',
          puzzle: 'Erstellen Sie Paare, die zusammenpassen',
          puzzle_matches: 'Definieren Sie, welche linken Elemente zu welchen rechten gehören',
          pin_count: '{count} Pin(s) gesetzt',
          lesson_content: 'Unterstützt Text-Formatierung mit Markdown'
        },
        prompts: {
          enter_image_url: 'Bitte Bild-URL eingeben:',
          enter_video_url: 'Bitte Video-URL eingeben:',
          enter_file_url: 'Bitte Datei-URL eingeben:'
        },
        messages: {
          load_error: 'Daten konnten nicht geladen werden.',
          save_success: 'Gespeichert.',
          save_error: 'Speichern fehlgeschlagen.',
          delete_success: 'Eintrag gelöscht.',
          delete_error: 'Eintrag konnte nicht gelöscht werden.',
          confirm_delete: 'Wirklich löschen?',
          confirm_delete_topic: 'Dieses Thema wirklich löschen?',
          confirm_delete_lesson: 'Diese Lektion wirklich löschen?',
          confirm_delete_quiz: 'Diesen Test wirklich löschen?',
          confirm_delete_question: 'Diese Frage wirklich löschen?',
          no_courses: 'Keine Kurse vorhanden.',
          no_training: 'Keine Ausbildungskarten vorhanden.',
          no_topics: 'Keine Themen vorhanden. Fügen Sie ein Thema hinzu, um zu beginnen.',
          no_lessons: 'Keine Lektionen vorhanden.',
          no_quizzes: 'Keine Tests vorhanden.',
          no_questions: 'Keine Fragen vorhanden.',
          no_videos: 'Keine Videos vorhanden.',
          no_files: 'Keine Dateien vorhanden.'
        },
        errors: {
          title_required: 'Titel ist erforderlich',
          description_required: 'Beschreibung ist erforderlich',
          topic_title_required: 'Thementitel ist erforderlich',
          lesson_name_required: 'Lektionsname ist erforderlich',
          quiz_title_required: 'Test-Titel ist erforderlich',
          question_text_required: 'Fragestellung ist erforderlich',
          points_required: 'Punkte müssen mindestens 1 sein'
        }
      }
    }
  },
  en: {
    mod: {
      academy: {
        title: 'Academy',
        tabs: {
          courses: 'Courses',
          training: 'Training plans'
        },
        course_tabs: {
          definition: 'Course Definition',
          planning: 'Course Planning'
        },
        quiz_tabs: {
          general: 'General',
          questions: 'Questions',
          settings: 'Settings'
        },
        actions: {
          add_course: 'Add course',
          add_card: 'Create training card',
          add_topic: 'Add topic',
          add_lesson: 'Add lesson',
          add_quiz: 'Add quiz',
          add_question: 'Add question',
          add_option: 'Add option',
          add_answer: 'Add answer',
          add_pair: 'Add pair',
          add_item: 'Add item',
          add_image_url: 'Add image URL',
          add_video: 'Add video',
          add_file: 'Add file',
          edit_topic: 'Edit topic',
          edit_lesson: 'Edit lesson',
          edit_quiz: 'Edit quiz',
          edit_question: 'Edit question',
          save: 'Save',
          cancel: 'Cancel',
          delete: 'Delete',
          refresh: 'Refresh',
          progress: 'Update progress'
        },
        labels: {
          title: 'Title',
          description: 'Description',
          category: 'Category',
          level: 'Difficulty Level',
          modules: 'Modules',
          module_title: 'Module',
          module_goal: 'Learning goal',
          duration: 'Duration (minutes)',
          student: 'Student',
          instructor: 'Instructor',
          program: 'Programme',
          notes: 'Notes',
          progress: 'Progress',
          milestones: 'Milestones',
          add_milestone: 'Add milestone',
          mark_completed: 'Complete',
          remove: 'Remove',

          // Course definition
          course_type: 'Course Type',
          author: 'Author',
          max_participants: 'Max Participants',
          visibility: 'Visibility',
          display_from: 'Display from',
          display_until: 'Display until',
          tags: 'Tags',
          featured_image: 'Featured Image',
          intro_video: 'Intro Video',
          sequential_topics: 'Topics must be completed in order',

          // Course planning
          topics: 'Topics',
          topic_title: 'Topic Title',
          topic_summary: 'Summary',
          lessons: 'Lessons',
          quizzes: 'Quizzes',
          lesson_name: 'Lesson Name',
          lesson_content: 'Content',
          images: 'Images',
          videos: 'Videos',
          files: 'Exercise Files',

          // Quiz
          quiz_title: 'Quiz Title',
          quiz_summary: 'Summary',
          questions: 'Questions',
          question: 'Question',
          question_type: 'Question Type',
          question_text: 'Question Text',
          time_limit: 'Time Limit (seconds)',
          points: 'Points',
          answer_options: 'Answer Options',
          correct: 'Correct',
          correct_answer: 'Correct Answer',
          correct_answer_type: 'Correct Answer Type',
          true: 'True',
          false: 'False',
          min: 'Min',
          max: 'Max',
          step: 'Step',
          exact_value: 'Exact Value',
          correct_value: 'Correct Value',
          range: 'Range',
          range_min: 'Range Min',
          range_max: 'Range Max',
          image_url: 'Image URL',
          sample_answer: 'Sample Answer',
          text_with_blanks: 'Text with Blanks',
          answers: 'Answers',
          blank: 'Blank',
          matching_pairs: 'Matching Pairs',
          left_items: 'Left Items',
          right_items: 'Right Items',
          correct_matches: 'Correct Matches',
          items_to_sort: 'Items to Sort',
          item: 'Item',

          // Quiz settings
          attempts_allowed: 'Attempts Allowed',
          unlimited: 'Unlimited',
          limited: 'Limited',
          pass_percentage: 'Pass Percentage',
          pass_threshold: 'Pass Threshold',
          questions_to_show: 'Questions to Show',
          all_questions: 'All Questions',
          random_selection: 'Random Selection',
          answer_order: 'Answer Order',
          randomize_answers: 'Randomize Answers',
          question_layout: 'Question Layout',
          show_feedback: 'Show Feedback'
        },
        sections: {
          basic_info: 'Basic Information',
          participation: 'Participation & Visibility',
          categorization: 'Categorization',
          media: 'Media',
          quiz_attempts: 'Attempt Settings',
          quiz_display: 'Display Settings',
          question_data: 'Question Details'
        },
        course_types: {
          online: 'Online',
          physical: 'In-Person'
        },
        visibility: {
          public: 'Public',
          logged_in: 'Logged-in users only',
          private: 'Private'
        },
        levels: {
          beginner: 'Beginner',
          intermediate: 'Intermediate',
          advanced: 'Advanced'
        },
        question_types: {
          quiz_single: 'Quiz (Single Choice)',
          quiz_multiple: 'Quiz (Multiple Choice)',
          true_false: 'True/False',
          slider: 'Slider',
          pin_answer: 'Pin Answer',
          essay: 'Essay/Open Question',
          fill_blank: 'Fill in the Blanks',
          short_answer: 'Short Answer',
          matching: 'Matching',
          image_answer: 'Image Answer',
          sorting: 'Sorting',
          puzzle: 'Puzzle (Drag & Drop)'
        },
        layouts: {
          single: 'Single questions (with "Next" button)',
          all: 'All questions together (with "Finish")'
        },
        feedback: {
          immediate: 'Show immediately',
          end: 'Show at the end'
        },
        placeholders: {
          topic_title: 'Enter topic title...',
          lesson_name: 'Enter lesson name...',
          lesson_content: 'Enter lesson content here...',
          quiz_title: 'Enter quiz title...',
          option_text: 'Enter answer option...',
          add_tag: 'Add tag...',
          left_item: 'Left item',
          right_item: 'Right item'
        },
        help: {
          max_participants: 'Leave empty for unlimited participants',
          intro_video: 'YouTube URL or direct link to video file',
          time_limit: 'Leave empty for no time limit',
          sample_answer: 'Optional: Sample answer for guidance',
          fill_blank: 'Use ___ for blanks in the text',
          sorting: 'Drag items to set correct order',
          puzzle: 'Create pairs that match',
          puzzle_matches: 'Define which left items match which right items',
          pin_count: '{count} pin(s) placed',
          lesson_content: 'Supports text formatting with Markdown'
        },
        prompts: {
          enter_image_url: 'Please enter image URL:',
          enter_video_url: 'Please enter video URL:',
          enter_file_url: 'Please enter file URL:'
        },
        messages: {
          load_error: 'Failed to load academy data.',
          save_success: 'Saved.',
          save_error: 'Failed to save.',
          delete_success: 'Entry removed.',
          delete_error: 'Could not delete entry.',
          confirm_delete: 'Really delete?',
          confirm_delete_topic: 'Really delete this topic?',
          confirm_delete_lesson: 'Really delete this lesson?',
          confirm_delete_quiz: 'Really delete this quiz?',
          confirm_delete_question: 'Really delete this question?',
          no_courses: 'No courses available.',
          no_training: 'No training cards available.',
          no_topics: 'No topics available. Add a topic to get started.',
          no_lessons: 'No lessons available.',
          no_quizzes: 'No quizzes available.',
          no_questions: 'No questions available.',
          no_videos: 'No videos available.',
          no_files: 'No files available.'
        },
        errors: {
          title_required: 'Title is required',
          description_required: 'Description is required',
          topic_title_required: 'Topic title is required',
          lesson_name_required: 'Lesson name is required',
          quiz_title_required: 'Quiz title is required',
          question_text_required: 'Question text is required',
          points_required: 'Points must be at least 1'
        }
      }
    }
  },
  // Simplified translations for other languages - can be expanded later
  fr: {
    mod: {
      academy: {
        title: 'Académie',
        tabs: { courses: 'Cours', training: 'Cartes de formation' },
        course_tabs: { definition: 'Définition du cours', planning: 'Planification du cours' },
        // ... (can be expanded with full translations)
      }
    }
  },
  it: {
    mod: {
      academy: {
        title: 'Accademia',
        tabs: { courses: 'Corsi', training: 'Schede formazione' },
        course_tabs: { definition: 'Definizione del corso', planning: 'Pianificazione del corso' },
        // ... (can be expanded with full translations)
      }
    }
  },
  es: {
    mod: {
      academy: {
        title: 'Academia',
        tabs: { courses: 'Cursos', training: 'Planes de formación' },
        course_tabs: { definition: 'Definición del curso', planning: 'Planificación del curso' },
        // ... (can be expanded with full translations)
      }
    }
  }
}
