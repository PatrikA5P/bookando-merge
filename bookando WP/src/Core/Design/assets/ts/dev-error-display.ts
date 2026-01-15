/**
 * Development Error Display System
 *
 * Provides visual error feedback during module loading.
 * Only active when WP_DEBUG is enabled.
 */

export interface LoadingCheckpoint {
  name: string
  timestamp: number
  status: 'success' | 'error' | 'pending'
  error?: Error
  details?: Record<string, any>
}

class DevErrorDisplay {
  private checkpoints: LoadingCheckpoint[] = []
  private moduleSlug: string = 'unknown'
  private rootElement: HTMLElement | null = null
  private isDevMode: boolean = false

  constructor() {
    // Check if WP_DEBUG is enabled via inline script
    this.isDevMode = !!(window as any).BOOKANDO_VARS?.debug || import.meta?.env?.DEV
  }

  /**
   * Initialize error display for a module
   */
  init(moduleSlug: string, rootElement: HTMLElement | null): void {
    this.moduleSlug = moduleSlug
    this.rootElement = rootElement

    if (this.isDevMode) {
      console.log(`[DevErrorDisplay] Initialized for module: ${moduleSlug}`)
    }
  }

  /**
   * Log a checkpoint during loading
   */
  checkpoint(name: string, details?: Record<string, any>): void {
    const checkpoint: LoadingCheckpoint = {
      name,
      timestamp: Date.now(),
      status: 'success',
      details
    }

    this.checkpoints.push(checkpoint)

    if (this.isDevMode) {
      console.log(`[${this.moduleSlug}] ✅ ${name}`, details || '')
    }
  }

  /**
   * Log an error and display it visually
   */
  error(name: string, error: Error, details?: Record<string, any>): void {
    const checkpoint: LoadingCheckpoint = {
      name,
      timestamp: Date.now(),
      status: 'error',
      error,
      details
    }

    this.checkpoints.push(checkpoint)

    console.error(`[${this.moduleSlug}] ❌ ${name}`, error, details || '')

    // Display visual error if in dev mode
    if (this.isDevMode && this.rootElement) {
      this.renderErrorDisplay(error, name, details)
    }
  }

  /**
   * Render visual error display in the root element
   */
  private renderErrorDisplay(error: Error, checkpoint: string, details?: Record<string, any>): void {
    if (!this.rootElement) return

    const errorHtml = `
      <div style="
        background: #fee;
        border: 2px solid #c33;
        border-radius: 8px;
        padding: 20px;
        margin: 20px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      ">
        <h2 style="
          color: #c33;
          margin: 0 0 16px 0;
          font-size: 18px;
          display: flex;
          align-items: center;
          gap: 8px;
        ">
          <span style="font-size: 24px;">⚠️</span>
          Module Loading Error [DEV MODE]
        </h2>

        <div style="background: white; padding: 12px; border-radius: 4px; margin-bottom: 12px;">
          <strong style="color: #333;">Module:</strong>
          <code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">${this.moduleSlug}</code>
        </div>

        <div style="background: white; padding: 12px; border-radius: 4px; margin-bottom: 12px;">
          <strong style="color: #333;">Failed at:</strong>
          <code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">${this.escapeHtml(checkpoint)}</code>
        </div>

        <div style="background: white; padding: 12px; border-radius: 4px; margin-bottom: 12px;">
          <strong style="color: #c33;">Error:</strong><br>
          <code style="
            display: block;
            background: #f5f5f5;
            padding: 8px;
            border-radius: 3px;
            margin-top: 8px;
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 13px;
          ">${this.escapeHtml(error.message)}</code>
        </div>

        ${error.stack ? `
          <details style="background: white; padding: 12px; border-radius: 4px; margin-bottom: 12px;">
            <summary style="cursor: pointer; font-weight: bold; color: #666;">Stack Trace</summary>
            <pre style="
              margin: 8px 0 0 0;
              padding: 8px;
              background: #f5f5f5;
              border-radius: 3px;
              overflow-x: auto;
              font-size: 11px;
              line-height: 1.4;
            ">${this.escapeHtml(error.stack)}</pre>
          </details>
        ` : ''}

        ${details ? `
          <details style="background: white; padding: 12px; border-radius: 4px; margin-bottom: 12px;">
            <summary style="cursor: pointer; font-weight: bold; color: #666;">Additional Details</summary>
            <pre style="
              margin: 8px 0 0 0;
              padding: 8px;
              background: #f5f5f5;
              border-radius: 3px;
              overflow-x: auto;
              font-size: 11px;
              line-height: 1.4;
            ">${this.escapeHtml(JSON.stringify(details, null, 2))}</pre>
          </details>
        ` : ''}

        ${this.checkpoints.length > 0 ? `
          <details style="background: white; padding: 12px; border-radius: 4px;">
            <summary style="cursor: pointer; font-weight: bold; color: #666;">
              Loading Timeline (${this.checkpoints.length} checkpoints)
            </summary>
            <div style="margin-top: 12px;">
              ${this.checkpoints.map((cp, idx) => `
                <div style="
                  display: flex;
                  align-items: flex-start;
                  gap: 8px;
                  padding: 8px;
                  background: ${cp.status === 'error' ? '#fee' : '#f5f5f5'};
                  border-left: 3px solid ${cp.status === 'error' ? '#c33' : '#4a4'};
                  margin-bottom: 4px;
                  border-radius: 3px;
                  font-size: 13px;
                ">
                  <span style="
                    min-width: 24px;
                    font-weight: bold;
                    color: ${cp.status === 'error' ? '#c33' : '#666'};
                  ">${idx + 1}.</span>
                  <div style="flex: 1;">
                    <div style="font-weight: bold; color: ${cp.status === 'error' ? '#c33' : '#333'};">
                      ${cp.status === 'error' ? '❌' : '✅'} ${this.escapeHtml(cp.name)}
                    </div>
                    ${cp.details ? `
                      <pre style="
                        margin: 4px 0 0 0;
                        font-size: 11px;
                        color: #666;
                        white-space: pre-wrap;
                      ">${this.escapeHtml(JSON.stringify(cp.details, null, 2))}</pre>
                    ` : ''}
                  </div>
                  <span style="
                    font-size: 11px;
                    color: #999;
                    white-space: nowrap;
                  ">+${this.getTimeDiff(idx)}ms</span>
                </div>
              `).join('')}
            </div>
          </details>
        ` : ''}

        <div style="
          margin-top: 16px;
          padding: 12px;
          background: #ffd;
          border-left: 4px solid #fa0;
          border-radius: 4px;
          font-size: 13px;
        ">
          <strong>💡 Debugging Tips:</strong>
          <ul style="margin: 8px 0 0 0; padding-left: 20px;">
            <li>Check the browser console for detailed logs</li>
            <li>Verify BOOKANDO_VARS is properly initialized</li>
            <li>Check network tab for failed asset loading</li>
            <li>Ensure root element exists in DOM</li>
            <li>Check for JavaScript syntax errors in Vue components</li>
          </ul>
        </div>

        <div style="margin-top: 12px; font-size: 12px; color: #666; text-align: center;">
          This error display only appears in development mode
        </div>
      </div>
    `

    this.rootElement.innerHTML = errorHtml
  }

  /**
   * Calculate time difference from previous checkpoint
   */
  private getTimeDiff(index: number): number {
    if (index === 0) return 0
    return this.checkpoints[index].timestamp - this.checkpoints[index - 1].timestamp
  }

  /**
   * Escape HTML to prevent XSS
   */
  private escapeHtml(text: string): string {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
  }

  /**
   * Get all checkpoints (for debugging)
   */
  getCheckpoints(): LoadingCheckpoint[] {
    return [...this.checkpoints]
  }

  /**
   * Export checkpoints as JSON for bug reports
   */
  exportCheckpoints(): string {
    return JSON.stringify({
      module: this.moduleSlug,
      checkpoints: this.checkpoints,
      userAgent: navigator.userAgent,
      timestamp: new Date().toISOString(),
      bookandoVars: (window as any).BOOKANDO_VARS
    }, null, 2)
  }
}

// Global singleton instance
export const devErrorDisplay = new DevErrorDisplay()

// Make available globally for debugging
if (typeof window !== 'undefined') {
  (window as any).__BOOKANDO_DEV__ = {
    errorDisplay: devErrorDisplay,
    exportDebugInfo: () => devErrorDisplay.exportCheckpoints()
  }
}
