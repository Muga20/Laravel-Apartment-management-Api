<!doctype html>
<html lang="en">
<head>
  <style>
    body {
      background-color: #f3f4f6;
    }
    .container {
      max-width: 42rem;
      padding: 1.5rem;
      margin: 0 auto;
      background-color: #fff;
      border-radius: 0.5rem;
      box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .header-logo {
      display: block;
      width: auto;
      height: 2.25rem;
    }
    .content {
      margin-top: 2rem;
      color: #4b5563;
    }
    .content h2 {
      font-size: 1.5rem;
      font-weight: 600;
      color: #374151;
    }
    .content p {
      margin-top: 1rem;
      font-size: 1rem;
      line-height: 1.5;
    }
    .footer {
      margin-top: 2rem;
      font-size: 0.875rem;
      color: #6b7280;
    }
  </style>
</head>
<body>

<section class="container">
  <header>
    <a href="#">
      <img class="header-logo" src="https://merakiui.com/images/full-logo.svg" alt="Logo">
    </a>
  </header>
  <main class="content">
    <h2>Hi {{$user->detail->first_name}} {{$user->detail->middle_name}} {{$user->detail->last_name}},</h2>
    <p>Your email has been successfully updated to {{$user->email}}. You can now use your new email address to log in to your account. If you have any questions or need further assistance, feel free to reach out to us.</p>
  </main>
  <footer class="footer">
    <p>&copy; {{ date('Y') }} {{ config('appName.name') }}. All Rights Reserved.</p>
  </footer>
</section>

</body>
</html>
