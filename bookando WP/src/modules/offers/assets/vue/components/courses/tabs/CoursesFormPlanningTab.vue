<template>
  <section
    class="courses-form__panel"
    role="tabpanel"
    tabindex="0"
  >
    <AppServicesFormSection
      icon="timeline"
      :title="t('mod.offers.courses.planning.section.settings') || 'Kurslogik'"
      :description="t('mod.offers.courses.planning.section.settings_hint') || 'Bestimme, wie dein Kurs durchlaufen wird.'"
      layout="stack"
      compact
    >
      <BookandoField
        id="course_topics_sequential"
        v-model="form.topics_sequential"
        type="toggle"
        :label="t('mod.offers.courses.planning.sequential') || 'Themen müssen in Reihenfolge absolviert werden'"
        :row="true"
      />
    </AppServicesFormSection>

    <AppServicesFormSection
      icon="layers"
      :title="t('mod.offers.courses.planning.section.topics') || 'Themen & Inhalte'"
      :description="t('mod.offers.courses.planning.section.topics_hint') || 'Strukturiere den Kurs in Themen mit Lektionen und Tests.'"
      layout="stack"
    >
      <div class="courses-form__topics">
        <article
          v-for="(topic, topicIndex) in form.topics"
          :key="topic._localId"
          class="courses-form__topic"
        >
          <header class="courses-form__topic-header">
            <h3>
              {{ topic.title || `${t('mod.offers.courses.planning.topic') || 'Thema'} ${topicIndex + 1}` }}
            </h3>
            <div class="courses-form__topic-actions">
              <AppButton
                v-if="topicIndex > 0"
                size="sm"
                variant="ghost"
                icon="arrow-up"
                @click="moveTopic(topicIndex, topicIndex - 1)"
              />
              <AppButton
                v-if="topicIndex < form.topics.length - 1"
                size="sm"
                variant="ghost"
                icon="arrow-down"
                @click="moveTopic(topicIndex, topicIndex + 1)"
              />
              <AppButton
                size="sm"
                variant="ghost"
                icon="trash"
                @click="removeTopic(topicIndex)"
              />
            </div>
          </header>

          <div class="courses-form__topic-body">
            <BookandoField
              :id="`topic_title_${topic._localId}`"
              v-model="topic.title"
              type="text"
              :label="t('fields.title') || 'Titel'"
              required
            />

            <AppRichTextField
              :id="`topic_summary_${topic._localId}`"
              v-model="topic.summary"
              :label="t('core.common.summary') || 'Zusammenfassung'"
              :min-height="160"
            />

            <section class="courses-form__lessons">
              <header class="courses-form__section-heading">
                <h4>{{ t('mod.offers.courses.planning.lessons') || 'Lektionen' }}</h4>
                <AppButton
                  size="sm"
                  variant="secondary"
                  icon="plus"
                  @click="addLesson(topicIndex)"
                >
                  {{ t('mod.offers.courses.planning.add_lesson') || 'Lektion hinzufügen' }}
                </AppButton>
              </header>

              <div
                v-if="topic.lessons.length"
                class="courses-form__lesson-list"
              >
                <article
                  v-for="(lesson, lessonIndex) in topic.lessons"
                  :key="lesson._localId"
                  class="courses-form__lesson"
                >
                  <header class="courses-form__lesson-header">
                    <h5>
                      {{ lesson.name || `${t('mod.offers.courses.planning.lesson') || 'Lektion'} ${lessonIndex + 1}` }}
                    </h5>
                    <div class="courses-form__lesson-actions">
                      <AppButton
                        v-if="lessonIndex > 0"
                        size="xs"
                        variant="ghost"
                        icon="arrow-up"
                        @click="moveLesson(topicIndex, lessonIndex, lessonIndex - 1)"
                      />
                      <AppButton
                        v-if="lessonIndex < topic.lessons.length - 1"
                        size="xs"
                        variant="ghost"
                        icon="arrow-down"
                        @click="moveLesson(topicIndex, lessonIndex, lessonIndex + 1)"
                      />
                      <AppButton
                        size="xs"
                        variant="ghost"
                        icon="trash"
                        @click="removeLesson(topicIndex, lessonIndex)"
                      />
                    </div>
                  </header>

                  <BookandoField
                    :id="`lesson_name_${lesson._localId}`"
                    v-model="lesson.name"
                    type="text"
                    :label="t('fields.title') || 'Titel'"
                    required
                  />

                  <AppRichTextField
                    :id="`lesson_content_${lesson._localId}`"
                    v-model="lesson.content"
                    :label="t('fields.content') || 'Inhalt'"
                    :min-height="180"
                  />

                  <div class="courses-form__lesson-resources">
                    <div class="courses-form__resource-block">
                      <div class="courses-form__resource-heading">
                        <h6>{{ t('mod.offers.courses.planning.lesson_images') || 'Beitragsbilder' }}</h6>
                        <AppButton
                          size="xs"
                          variant="ghost"
                          icon="plus"
                          @click="addLessonMedia(topicIndex, lessonIndex, 'images')"
                        />
                      </div>
                      <div
                        v-for="(image, imageIndex) in lesson.images"
                        :key="image._localId"
                        class="courses-form__resource-row"
                      >
                        <BookandoField
                          :id="`lesson_image_label_${lesson._localId}_${image._localId}`"
                          v-model="image.label"
                          type="text"
                          :label="t('fields.label') || 'Titel'"
                        />
                        <BookandoField
                          :id="`lesson_image_url_${lesson._localId}_${image._localId}`"
                          v-model="image.url"
                          type="text"
                          :label="t('fields.url') || 'URL'"
                        />
                        <AppButton
                          size="xs"
                          variant="ghost"
                          icon="trash"
                          @click="removeLessonMedia(topicIndex, lessonIndex, 'images', imageIndex)"
                        />
                      </div>
                    </div>

                    <div class="courses-form__resource-block">
                      <div class="courses-form__resource-heading">
                        <h6>{{ t('mod.offers.courses.planning.lesson_videos') || 'Videos' }}</h6>
                        <AppButton
                          size="xs"
                          variant="ghost"
                          icon="plus"
                          @click="addLessonMedia(topicIndex, lessonIndex, 'videos')"
                        />
                      </div>
                      <div
                        v-for="(video, videoIndex) in lesson.videos"
                        :key="video._localId"
                        class="courses-form__resource-row"
                      >
                        <BookandoField
                          :id="`lesson_video_label_${lesson._localId}_${video._localId}`"
                          v-model="video.label"
                          type="text"
                          :label="t('fields.label') || 'Titel'"
                        />
                        <BookandoField
                          :id="`lesson_video_url_${lesson._localId}_${video._localId}`"
                          v-model="video.url"
                          type="text"
                          :label="t('fields.url') || 'URL'"
                        />
                        <AppButton
                          size="xs"
                          variant="ghost"
                          icon="trash"
                          @click="removeLessonMedia(topicIndex, lessonIndex, 'videos', videoIndex)"
                        />
                      </div>
                    </div>

                    <div class="courses-form__resource-block">
                      <div class="courses-form__resource-heading">
                        <h6>{{ t('mod.offers.courses.planning.lesson_resources') || 'Übungsdateien' }}</h6>
                        <AppButton
                          size="xs"
                          variant="ghost"
                          icon="plus"
                          @click="addLessonMedia(topicIndex, lessonIndex, 'resources')"
                        />
                      </div>
                      <div
                        v-for="(resource, resourceIndex) in lesson.resources"
                        :key="resource._localId"
                        class="courses-form__resource-row"
                      >
                        <BookandoField
                          :id="`lesson_resource_label_${lesson._localId}_${resource._localId}`"
                          v-model="resource.label"
                          type="text"
                          :label="t('fields.label') || 'Titel'"
                        />
                        <BookandoField
                          :id="`lesson_resource_url_${lesson._localId}_${resource._localId}`"
                          v-model="resource.url"
                          type="text"
                          :label="t('fields.url') || 'URL'"
                        />
                        <AppButton
                          size="xs"
                          variant="ghost"
                          icon="trash"
                          @click="removeLessonMedia(topicIndex, lessonIndex, 'resources', resourceIndex)"
                        />
                      </div>
                    </div>
                  </div>
                </article>
              </div>

              <p
                v-else
                class="courses-form__empty"
              >
                {{ t('mod.offers.courses.planning.empty_lessons') || 'Noch keine Lektionen hinzugefügt.' }}
              </p>
            </section>

            <section class="courses-form__tests">
              <header class="courses-form__section-heading">
                <h4>{{ t('mod.offers.courses.planning.tests') || 'Tests' }}</h4>
                <AppButton
                  size="sm"
                  variant="secondary"
                  icon="plus"
                  @click="addTest(topicIndex)"
                >
                  {{ t('mod.offers.courses.planning.add_test') || 'Test hinzufügen' }}
                </AppButton>
              </header>

              <div
                v-if="topic.tests.length"
                class="courses-form__test-list"
              >
                <article
                  v-for="(test, testIndex) in topic.tests"
                  :key="test._localId"
                  class="courses-form__test"
                >
                  <header class="courses-form__test-header">
                    <h5>
                      {{ test.title || `${t('mod.offers.courses.planning.test') || 'Test'} ${testIndex + 1}` }}
                    </h5>
                    <div class="courses-form__lesson-actions">
                      <AppButton
                        v-if="testIndex > 0"
                        size="xs"
                        variant="ghost"
                        icon="arrow-up"
                        @click="moveTest(topicIndex, testIndex, testIndex - 1)"
                      />
                      <AppButton
                        v-if="testIndex < topic.tests.length - 1"
                        size="xs"
                        variant="ghost"
                        icon="arrow-down"
                        @click="moveTest(topicIndex, testIndex, testIndex + 1)"
                      />
                      <AppButton
                        size="xs"
                        variant="ghost"
                        icon="trash"
                        @click="removeTest(topicIndex, testIndex)"
                      />
                    </div>
                  </header>

                  <BookandoField
                    :id="`test_title_${test._localId}`"
                    v-model="test.title"
                    type="text"
                    :label="t('fields.title') || 'Titel'"
                    required
                  />

                  <AppRichTextField
                    :id="`test_summary_${test._localId}`"
                    v-model="test.summary"
                    :label="t('core.common.summary') || 'Zusammenfassung'"
                    :min-height="160"
                  />

                  <div class="courses-form__test-settings">
                    <h6>{{ t('mod.offers.courses.planning.test_settings') || 'Allgemeine Einstellungen' }}</h6>
                    <div class="courses-form__test-settings-grid">
                      <BookandoField
                        :id="`test_attempts_${test._localId}`"
                        v-model="test.settings.attemptsMode"
                        type="dropdown"
                        :label="t('mod.offers.courses.planning.attempts') || 'Zulässige Versuche'"
                        :options="attemptOptions"
                        option-label="label"
                        option-value="value"
                        mode="basic"
                      />

                      <BookandoField
                        v-if="test.settings.attemptsMode === 'limited'"
                        :id="`test_attempts_value_${test._localId}`"
                        v-model="test.settings.attemptsValue"
                        type="number"
                        min="1"
                        :label="t('mod.offers.courses.planning.attempts_value') || 'Max. Anzahl'"
                      />

                      <BookandoField
                        :id="`test_min_score_${test._localId}`"
                        v-model="test.settings.minScore"
                        type="number"
                        min="0"
                        max="100"
                        :label="t('mod.offers.courses.planning.min_score') || 'Mindestpunktzahl (%)'"
                      />

                      <BookandoField
                        :id="`test_question_selection_${test._localId}`"
                        v-model="test.settings.questionSelection"
                        type="dropdown"
                        :label="t('mod.offers.courses.planning.question_selection') || 'Anzahl Fragen'"
                        :options="questionSelectionOptions"
                        option-label="label"
                        option-value="value"
                        mode="basic"
                      />

                      <BookandoField
                        v-if="test.settings.questionSelection === 'limited'"
                        :id="`test_question_count_${test._localId}`"
                        v-model="test.settings.questionCount"
                        type="number"
                        min="1"
                        :label="t('mod.offers.courses.planning.question_count_value') || 'Anzahl auswählen'"
                      />

                      <BookandoField
                        :id="`test_shuffle_${test._localId}`"
                        v-model="test.settings.shuffleAnswers"
                        type="dropdown"
                        :label="t('mod.offers.courses.planning.shuffle_answers') || 'Reihenfolge der Antworten'"
                        :options="shuffleOptions"
                        option-label="label"
                        option-value="value"
                        mode="basic"
                      />

                      <BookandoField
                        :id="`test_layout_${test._localId}`"
                        v-model="test.settings.layout"
                        type="dropdown"
                        :label="t('mod.offers.courses.planning.layout') || 'Layout'"
                        :options="layoutOptions"
                        option-label="label"
                        option-value="value"
                        mode="basic"
                      />

                      <BookandoField
                        :id="`test_feedback_${test._localId}`"
                        v-model="test.settings.feedback"
                        type="dropdown"
                        :label="t('mod.offers.courses.planning.feedback') || 'Richtig/Falsch anzeigen'"
                        :options="feedbackOptions"
                        option-label="label"
                        option-value="value"
                        mode="basic"
                      />
                    </div>
                  </div>

                  <div class="courses-form__questions">
                    <header class="courses-form__section-heading">
                      <h6>{{ t('mod.offers.courses.planning.questions') || 'Fragen' }}</h6>
                      <AppButton
                        size="sm"
                        variant="secondary"
                        icon="plus"
                        @click="addQuestion(topicIndex, testIndex)"
                      >
                        {{ t('mod.offers.courses.planning.add_question') || 'Frage hinzufügen' }}
                      </AppButton>
                    </header>

                    <div
                      v-if="test.questions.length"
                      class="courses-form__question-list"
                    >
                      <article
                        v-for="(question, questionIndex) in test.questions"
                        :key="question._localId"
                        class="courses-form__question"
                      >
                        <header class="courses-form__question-header">
                          <h6>
                            {{ question.prompt || `${t('mod.offers.courses.planning.question') || 'Frage'} ${questionIndex + 1}` }}
                          </h6>
                          <div class="courses-form__lesson-actions">
                            <AppButton
                              v-if="questionIndex > 0"
                              size="xs"
                              variant="ghost"
                              icon="arrow-up"
                              @click="moveQuestion(topicIndex, testIndex, questionIndex, questionIndex - 1)"
                            />
                            <AppButton
                              v-if="questionIndex < test.questions.length - 1"
                              size="xs"
                              variant="ghost"
                              icon="arrow-down"
                              @click="moveQuestion(topicIndex, testIndex, questionIndex, questionIndex + 1)"
                            />
                            <AppButton
                              size="xs"
                              variant="ghost"
                              icon="trash"
                              @click="removeQuestion(topicIndex, testIndex, questionIndex)"
                            />
                          </div>
                        </header>

                        <BookandoField
                          :id="`question_prompt_${question._localId}`"
                          v-model="question.prompt"
                          type="text"
                          :label="t('mod.offers.courses.planning.question_prompt') || 'Fragestellung'"
                          required
                        />

                        <div class="courses-form__question-grid">
                          <BookandoField
                            :id="`question_type_${question._localId}`"
                            v-model="question.type"
                            type="dropdown"
                            :label="t('mod.offers.courses.planning.question_type') || 'Fragetyp'"
                            :options="questionTypeOptions"
                            option-label="label"
                            option-value="value"
                            mode="basic"
                            @change="onQuestionTypeChange(question)"
                          />

                          <BookandoField
                            :id="`question_time_${question._localId}`"
                            v-model="question.time_limit"
                            type="number"
                            min="0"
                            :label="t('mod.offers.courses.planning.question_time') || 'Zeitlimit (Sek.)'"
                          />

                          <BookandoField
                            :id="`question_points_${question._localId}`"
                            v-model="question.points"
                            type="number"
                            min="0"
                            :label="t('mod.offers.courses.planning.question_points') || 'Punkte'"
                          />
                        </div>

                        <template v-if="question.type === 'quiz_single' || question.type === 'quiz_multi'">
                          <div class="courses-form__resource-heading courses-form__resource-heading--inline">
                            <h6>{{ t('mod.offers.courses.planning.answer_options') || 'Antwortmöglichkeiten' }}</h6>
                            <AppButton
                              size="xs"
                              variant="ghost"
                              icon="plus"
                              @click="addQuestionOption(question)"
                            />
                          </div>
                          <div
                            v-for="(option, optionIndex) in question.options"
                            :key="option._localId"
                            class="courses-form__resource-row courses-form__resource-row--answer"
                          >
                            <BookandoField
                              :id="`question_option_${question._localId}_${option._localId}`"
                              v-model="option.text"
                              type="text"
                              :label="t('fields.answer') || 'Antwort'"
                            />
                            <BookandoField
                              :id="`question_option_correct_${question._localId}_${option._localId}`"
                              v-model="option.isCorrect"
                              type="toggle"
                              :label="t('mod.offers.courses.planning.correct') || 'Korrekt'"
                              :row="true"
                            />
                            <AppButton
                              v-if="question.options.length > 2"
                              size="xs"
                              variant="ghost"
                              icon="trash"
                              @click="removeQuestionOption(question, optionIndex)"
                            />
                          </div>
                        </template>

                        <template v-else-if="question.type === 'true_false'">
                          <BookandoField
                            :id="`question_true_false_${question._localId}`"
                            v-model="question.options[0].isCorrect"
                            type="dropdown"
                            :options="trueFalseOptions"
                            option-label="label"
                            option-value="value"
                            :label="t('mod.offers.courses.planning.correct_answer') || 'Korrekte Antwort'"
                            mode="basic"
                            @change="onTrueFalseChange(question)"
                          />
                        </template>

                        <template v-else-if="question.type === 'slider'">
                          <div class="courses-form__slider-grid">
                            <BookandoField
                              :id="`question_slider_min_${question._localId}`"
                              v-model="question.slider!.min"
                              type="number"
                              :label="t('mod.offers.courses.planning.slider_min') || 'Minimum'"
                            />
                            <BookandoField
                              :id="`question_slider_max_${question._localId}`"
                              v-model="question.slider!.max"
                              type="number"
                              :label="t('mod.offers.courses.planning.slider_max') || 'Maximum'"
                            />
                            <BookandoField
                              :id="`question_slider_step_${question._localId}`"
                              v-model="question.slider!.step"
                              type="number"
                              :label="t('mod.offers.courses.planning.slider_step') || 'Schrittweite'"
                            />
                            <BookandoField
                              :id="`question_slider_correct_min_${question._localId}`"
                              v-model="question.slider!.correctMin"
                              type="number"
                              :label="t('mod.offers.courses.planning.slider_correct_min') || 'Richtiger Bereich von'"
                            />
                            <BookandoField
                              :id="`question_slider_correct_max_${question._localId}`"
                              v-model="question.slider!.correctMax"
                              type="number"
                              :label="t('mod.offers.courses.planning.slider_correct_max') || 'Richtiger Bereich bis'"
                            />
                          </div>
                        </template>

                        <template v-else-if="question.type === 'pin'">
                          <BookandoField
                            :id="`question_pin_background_${question._localId}`"
                            v-model="question.pinBackground"
                            type="text"
                            :label="t('mod.offers.courses.planning.pin_background') || 'Bild-URL'"
                          />
                          <div class="courses-form__resource-heading courses-form__resource-heading--inline">
                            <h6>{{ t('mod.offers.courses.planning.pin_targets') || 'Richtige Markierungen' }}</h6>
                            <AppButton
                              size="xs"
                              variant="ghost"
                              icon="plus"
                              @click="addPin(question)"
                            />
                          </div>
                          <div
                            v-for="(pin, pinIndex) in question.pins"
                            :key="pin._localId"
                            class="courses-form__resource-row courses-form__resource-row--answer"
                          >
                            <BookandoField
                              :id="`question_pin_${question._localId}_${pin._localId}`"
                              v-model="pin.label"
                              type="text"
                              :label="t('fields.label') || 'Beschreibung'"
                            />
                            <AppButton
                              size="xs"
                              variant="ghost"
                              icon="trash"
                              @click="removePin(question, pinIndex)"
                            />
                          </div>
                        </template>

                        <template v-else-if="question.type === 'matching'">
                          <div class="courses-form__resource-heading courses-form__resource-heading--inline">
                            <h6>{{ t('mod.offers.courses.planning.match_pairs') || 'Paare' }}</h6>
                            <AppButton
                              size="xs"
                              variant="ghost"
                              icon="plus"
                              @click="addMatchingPair(question)"
                            />
                          </div>
                          <div
                            v-for="(pair, pairIndex) in question.matchingPairs"
                            :key="pair._localId"
                            class="courses-form__resource-row courses-form__resource-row--answer"
                          >
                            <BookandoField
                              :id="`question_matching_left_${question._localId}_${pair._localId}`"
                              v-model="pair.left"
                              type="text"
                              :label="t('fields.prompt') || 'Begriff A'"
                            />
                            <BookandoField
                              :id="`question_matching_right_${question._localId}_${pair._localId}`"
                              v-model="pair.right"
                              type="text"
                              :label="t('fields.answer') || 'Begriff B'"
                            />
                            <AppButton
                              size="xs"
                              variant="ghost"
                              icon="trash"
                              @click="removeMatchingPair(question, pairIndex)"
                            />
                          </div>
                        </template>

                        <template v-else-if="question.type === 'puzzle'">
                          <div class="courses-form__resource-heading courses-form__resource-heading--inline">
                            <h6>{{ t('mod.offers.courses.planning.match_pairs') || 'Paare' }}</h6>
                            <AppButton
                              size="xs"
                              variant="ghost"
                              icon="plus"
                              @click="addPuzzlePair(question)"
                            />
                          </div>
                          <div
                            v-for="(pair, pairIndex) in question.puzzlePairs"
                            :key="pair._localId"
                            class="courses-form__resource-row courses-form__resource-row--answer"
                          >
                            <BookandoField
                              :id="`question_puzzle_left_${question._localId}_${pair._localId}`"
                              v-model="pair.left"
                              type="text"
                              :label="t('fields.prompt') || 'Begriff A'"
                            />
                            <BookandoField
                              :id="`question_puzzle_right_${question._localId}_${pair._localId}`"
                              v-model="pair.right"
                              type="text"
                              :label="t('fields.answer') || 'Begriff B'"
                            />
                            <AppButton
                              size="xs"
                              variant="ghost"
                              icon="trash"
                              @click="removePuzzlePair(question, pairIndex)"
                            />
                          </div>
                        </template>

                        <template v-else-if="question.type === 'ordering'">
                          <div class="courses-form__resource-heading courses-form__resource-heading--inline">
                            <h6>{{ t('mod.offers.courses.planning.order_items') || 'Elemente sortieren' }}</h6>
                            <AppButton
                              size="xs"
                              variant="ghost"
                              icon="plus"
                              @click="addOrderingItem(question)"
                            />
                          </div>
                          <div
                            v-for="(item, itemIndex) in question.orderingItems"
                            :key="item._localId"
                            class="courses-form__resource-row courses-form__resource-row--answer"
                          >
                            <BookandoField
                              :id="`question_ordering_${question._localId}_${item._localId}`"
                              v-model="item.text"
                              type="text"
                              :label="t('fields.item') || 'Element'"
                            />
                            <AppButton
                              size="xs"
                              variant="ghost"
                              icon="trash"
                              @click="removeOrderingItem(question, itemIndex)"
                            />
                          </div>
                        </template>

                        <template v-else>
                          <BookandoField
                            :id="`question_answer_${question._localId}`"
                            v-model="question.answerText"
                            type="textarea"
                            :label="t('mod.offers.courses.planning.answer_text') || 'Beispielantwort / Bewertungshinweis'"
                          />
                        </template>

                        <AppRichTextField
                          v-if="question.type === 'cloze'"
                          :id="`question_cloze_${question._localId}`"
                          v-model="question.clozeText"
                          :label="t('mod.offers.courses.planning.cloze_text') || 'Lückentext'"
                          :min-height="140"
                        />

                        <BookandoField
                          v-if="question.type === 'image'"
                          :id="`question_answer_image_${question._localId}`"
                          v-model="question.answerImage"
                          type="text"
                          :label="t('mod.offers.courses.planning.answer_image') || 'Referenzbild (URL)'"
                        />
                      </article>
                    </div>

                    <p
                      v-else
                      class="courses-form__empty"
                    >
                      {{ t('mod.offers.courses.planning.empty_questions') || 'Noch keine Fragen erstellt.' }}
                    </p>
                  </div>
                </article>
              </div>

              <p
                v-else
                class="courses-form__empty"
              >
                {{ t('mod.offers.courses.planning.empty_tests') || 'Noch keine Tests angelegt.' }}
              </p>
            </section>
          </div>
        </article>
      </div>

      <div class="courses-form__sessions-add">
        <AppButton
          size="md"
          variant="primary"
          icon="plus"
          @click="addTopic"
        >
          {{ t('mod.offers.courses.planning.add_topic') || 'Thema hinzufügen' }}
        </AppButton>
      </div>
    </AppServicesFormSection>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppRichTextField from '@core/Design/components/AppRichTextField.vue'
import AppServicesFormSection from '../../services/ui/AppServicesFormSection.vue'
import type { CourseFormVm, CourseQuestion, CourseTopic } from '../CoursesForm.vue'

type LessonMediaGroup = 'images' | 'videos' | 'resources'

type AttemptOption = { label: string; value: 'unlimited' | 'limited' }
type QuestionSelectionOption = { label: string; value: 'all' | 'limited' }
type ShuffleOption = { label: string; value: 'random' | 'fixed' }
type LayoutOption = { label: string; value: 'single' | 'list' }
type FeedbackOption = { label: string; value: 'instant' | 'end' }

type TrueFalseOption = { label: string; value: boolean }

type QuestionTypeOption = { label: string; value: CourseQuestion['type'] }

const model = defineModel<CourseFormVm>({ local: false })
const form = computed({
  get: () => model.value!,
  set: value => (model.value = value),
})

const { t } = useI18n()

const attemptOptions = computed<AttemptOption[]>(() => ([
  { label: t('mod.offers.courses.planning.attempt_unlimited') || 'Unbegrenzt', value: 'unlimited' },
  { label: t('mod.offers.courses.planning.attempt_limited') || 'Bis ...', value: 'limited' },
]))

const questionSelectionOptions = computed<QuestionSelectionOption[]>(() => ([
  { label: t('mod.offers.courses.planning.question_all') || 'Alle Fragen', value: 'all' },
  { label: t('mod.offers.courses.planning.question_limit') || 'Zufällige Auswahl', value: 'limited' },
]))

const shuffleOptions = computed<ShuffleOption[]>(() => ([
  { label: t('mod.offers.courses.planning.shuffle_random') || 'Zufällig', value: 'random' },
  { label: t('mod.offers.courses.planning.shuffle_fixed') || 'Nach Vorgabe', value: 'fixed' },
]))

const layoutOptions = computed<LayoutOption[]>(() => ([
  { label: t('mod.offers.courses.planning.layout_single') || 'Einzelfragen', value: 'single' },
  { label: t('mod.offers.courses.planning.layout_list') || 'Alle Fragen untereinander', value: 'list' },
]))

const feedbackOptions = computed<FeedbackOption[]>(() => ([
  { label: t('mod.offers.courses.planning.feedback_instant') || 'Sofort', value: 'instant' },
  { label: t('mod.offers.courses.planning.feedback_end') || 'Erst am Ende', value: 'end' },
]))

const trueFalseOptions = computed<TrueFalseOption[]>(() => ([
  { label: t('core.common.true') || 'Richtig', value: true },
  { label: t('core.common.false') || 'Falsch', value: false },
]))

const questionTypeOptions = computed<QuestionTypeOption[]>(() => ([
  { label: t('mod.offers.courses.planning.type.quiz_single') || 'Quiz – Einzelauswahl', value: 'quiz_single' },
  { label: t('mod.offers.courses.planning.type.quiz_multi') || 'Quiz – Mehrfachauswahl', value: 'quiz_multi' },
  { label: t('mod.offers.courses.planning.type.true_false') || 'Richtig / Falsch', value: 'true_false' },
  { label: t('mod.offers.courses.planning.type.slider') || 'Schieberegler', value: 'slider' },
  { label: t('mod.offers.courses.planning.type.pin') || 'Antwort anheften', value: 'pin' },
  { label: t('mod.offers.courses.planning.type.essay') || 'Aufsatz', value: 'essay' },
  { label: t('mod.offers.courses.planning.type.cloze') || 'Lückentext', value: 'cloze' },
  { label: t('mod.offers.courses.planning.type.short') || 'Kurzantwort', value: 'short_answer' },
  { label: t('mod.offers.courses.planning.type.matching') || 'Übereinstimmung', value: 'matching' },
  { label: t('mod.offers.courses.planning.type.image') || 'Bildbeantwortung', value: 'image' },
  { label: t('mod.offers.courses.planning.type.ordering') || 'Sortierung', value: 'ordering' },
  { label: t('mod.offers.courses.planning.type.puzzle') || 'Puzzle', value: 'puzzle' },
]))

function addTopic() {
  form.value.topics.push(createTopic())
}

function removeTopic(index: number) {
  form.value.topics.splice(index, 1)
}

function moveTopic(from: number, to: number) {
  const topics = form.value.topics
  const [entry] = topics.splice(from, 1)
  topics.splice(to, 0, entry)
}

function addLesson(topicIndex: number) {
  const lessons = form.value.topics[topicIndex].lessons
  lessons.push({
    _localId: nextId(lessons),
    name: '',
    content: '',
    images: [],
    videos: [],
    resources: [],
  })
}

function removeLesson(topicIndex: number, lessonIndex: number) {
  const lessons = form.value.topics[topicIndex].lessons
  lessons.splice(lessonIndex, 1)
}

function moveLesson(topicIndex: number, from: number, to: number) {
  const lessons = form.value.topics[topicIndex].lessons
  const [entry] = lessons.splice(from, 1)
  lessons.splice(to, 0, entry)
}

function addLessonMedia(topicIndex: number, lessonIndex: number, group: LessonMediaGroup) {
  const lesson = form.value.topics[topicIndex].lessons[lessonIndex]
  const target = lesson[group]
  target.push({ _localId: nextId(target), label: '', url: '' })
}

function removeLessonMedia(topicIndex: number, lessonIndex: number, group: LessonMediaGroup, mediaIndex: number) {
  const lesson = form.value.topics[topicIndex].lessons[lessonIndex]
  lesson[group].splice(mediaIndex, 1)
}

function addTest(topicIndex: number) {
  const tests = form.value.topics[topicIndex].tests
  tests.push({
    _localId: nextId(tests),
    title: '',
    summary: '',
    questions: [],
    settings: {
      attemptsMode: 'unlimited',
      attemptsValue: null,
      minScore: 0,
      questionSelection: 'all',
      questionCount: null,
      shuffleAnswers: 'random',
      layout: 'single',
      feedback: 'end',
    },
  })
}

function removeTest(topicIndex: number, testIndex: number) {
  const tests = form.value.topics[topicIndex].tests
  tests.splice(testIndex, 1)
}

function moveTest(topicIndex: number, from: number, to: number) {
  const tests = form.value.topics[topicIndex].tests
  const [entry] = tests.splice(from, 1)
  tests.splice(to, 0, entry)
}

function addQuestion(topicIndex: number, testIndex: number) {
  const test = form.value.topics[topicIndex].tests[testIndex]
  test.questions.push(createQuestion(test.questions))
}

function removeQuestion(topicIndex: number, testIndex: number, questionIndex: number) {
  const test = form.value.topics[topicIndex].tests[testIndex]
  test.questions.splice(questionIndex, 1)
}

function moveQuestion(topicIndex: number, testIndex: number, from: number, to: number) {
  const questions = form.value.topics[topicIndex].tests[testIndex].questions
  const [entry] = questions.splice(from, 1)
  questions.splice(to, 0, entry)
}

function addQuestionOption(question: CourseQuestion) {
  question.options.push({ _localId: nextId(question.options), text: '', isCorrect: false })
}

function removeQuestionOption(question: CourseQuestion, optionIndex: number) {
  question.options.splice(optionIndex, 1)
}

function addPin(question: CourseQuestion) {
  question.pins.push({ _localId: nextId(question.pins), label: '' })
}

function removePin(question: CourseQuestion, pinIndex: number) {
  question.pins.splice(pinIndex, 1)
}

function addOrderingItem(question: CourseQuestion) {
  question.orderingItems.push({ _localId: nextId(question.orderingItems), text: '' })
}

function removeOrderingItem(question: CourseQuestion, itemIndex: number) {
  question.orderingItems.splice(itemIndex, 1)
}

function onQuestionTypeChange(question: CourseQuestion) {
  if (question.type === 'quiz_single' || question.type === 'quiz_multi') {
    if (!question.options.length) {
      question.options.push({ _localId: 1, text: '', isCorrect: true })
      question.options.push({ _localId: 2, text: '', isCorrect: false })
    }
  }

  if (question.type === 'true_false') {
    question.options = [
      { _localId: 1, text: t('core.common.true') || 'Richtig', isCorrect: true },
      { _localId: 2, text: t('core.common.false') || 'Falsch', isCorrect: false },
    ]
  }

  if (question.type === 'slider') {
    question.slider = question.slider || { min: 0, max: 100, step: 5, correctMin: 40, correctMax: 60 }
  } else {
    question.slider = null
  }

  if (question.type === 'pin') {
    if (!question.pins.length) {
      question.pins.push({ _localId: 1, label: '' })
    }
  } else {
    question.pins = []
    question.pinBackground = ''
  }

  if (question.type === 'matching') {
    if (!question.matchingPairs.length) {
      question.matchingPairs.push({ _localId: 1, left: '', right: '' })
    }
    question.puzzlePairs = []
  } else if (question.type === 'puzzle') {
    if (!question.puzzlePairs.length) {
      question.puzzlePairs.push({ _localId: 1, left: '', right: '' })
    }
    question.matchingPairs = []
  } else {
    question.matchingPairs = []
    question.puzzlePairs = []
  }

  if (question.type === 'ordering') {
    if (!question.orderingItems.length) {
      question.orderingItems.push({ _localId: 1, text: '' })
      question.orderingItems.push({ _localId: 2, text: '' })
    }
  } else {
    question.orderingItems = []
  }

  if (question.type === 'essay' || question.type === 'short_answer' || question.type === 'image') {
    question.answerText = question.answerText || ''
  }

  if (question.type !== 'cloze') {
    question.clozeText = ''
  }

  if (question.type !== 'image') {
    question.answerImage = ''
  }

  if (!(question.type === 'quiz_single' || question.type === 'quiz_multi' || question.type === 'true_false')) {
    question.options = []
  }
}

function onTrueFalseChange(question: CourseQuestion) {
  const selected = question.options[0].isCorrect
  question.options[0].isCorrect = !!selected
  question.options[1].isCorrect = !selected
}

function createTopic(): CourseTopic {
  return {
    _localId: nextId(form.value.topics),
    title: '',
    summary: '',
    lessons: [],
    tests: [],
  }
}

function addMatchingPair(question: CourseQuestion) {
  question.matchingPairs.push({ _localId: nextId(question.matchingPairs), left: '', right: '' })
}

function removeMatchingPair(question: CourseQuestion, index: number) {
  question.matchingPairs.splice(index, 1)
}

function addPuzzlePair(question: CourseQuestion) {
  question.puzzlePairs.push({ _localId: nextId(question.puzzlePairs), left: '', right: '' })
}

function removePuzzlePair(question: CourseQuestion, index: number) {
  question.puzzlePairs.splice(index, 1)
}

function createQuestion(collection: CourseQuestion[]): CourseQuestion {
  return {
    _localId: nextId(collection),
    prompt: '',
    type: 'quiz_single',
    time_limit: null,
    points: 1,
    options: [
      { _localId: 1, text: '', isCorrect: true },
      { _localId: 2, text: '', isCorrect: false },
    ],
    slider: null,
    pins: [],
    pinBackground: '',
    matchingPairs: [],
    orderingItems: [],
    puzzlePairs: [],
    answerText: '',
    clozeText: '',
    answerImage: '',
  }
}

function nextId(collection: Array<{ _localId: number }>): number {
  const max = collection.reduce((acc, item) => Math.max(acc, item._localId || 0), 0)
  return max + 1
}
</script>
