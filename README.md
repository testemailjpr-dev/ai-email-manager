<h3>Workflow of the AI Email Manager App</h3>
<ol>
  <li>
    <p><strong>User Authentication:</strong><br />
      The user signs in using <strong>Google OAuth</strong>.</p>
  </li>
  <li>
    <p><strong>Permission Request:</strong><br />
      The app requests the necessary Gmail-related scopes to access email data.</p>
  </li>
  <li>
    <p><strong>Dashboard Display:</strong><br />
      After login, the user is directed to a dashboard with three main sections:</p>
    <ul>
      <li>
        <p>An option to <strong>Connect with another GMail account</strong>, allowing multi-account support.</p>
      </li>
      <li>
        <p>A list of custom categories for organizing emails.</p>
      </li>
      <li>
        <p>A form and button to add a new category.</p>
      </li>
    </ul>
  </li>
  <li>
    <p><strong>Email Import and Categorization:</strong><br />
      When new emails arrive (similar to the Gmail Inbox), the app automatically imports the latest emails, categorizes them using the <strong>OpenAI API</strong>, and adds an <strong>AI-generated summary</strong> for each email.</p>
  </li>
  <li>
    <p><strong>Archiving on Gmail:</strong><br />
      Once imported, the emails are <strong>archived</strong> in the Gmail account (not deleted).</p>
  </li>
  <li>
    <p><strong>Category View:</strong><br />
      When the user clicks on a category, the app displays all emails imported into that category. Each email includes an <strong>AI summary</strong>, and users can select individual or multiple emails to <strong>delete</strong> or <strong>unsubscribe</strong>.</p>
  </li>
  <li>
    <p><strong>Unsubscribe Automation:</strong><br />
      If the user select individual or multiple emails to unsubscribe, the app scans each selected email for an <strong>unsubscribe</strong> link and acts as an <strong>AI agent</strong> to visit the page and complete the unsubscription process (including filling out forms, toggling options, and submitting the user’s Gmail address if needed).</p>
  </li>
  <li>
    <p><strong>Email Details View:</strong><br />
      When the user clicks on an email, they can view the original email content along with its <strong>AI-generated summary</strong>.</p>
  </li>
</ol>
